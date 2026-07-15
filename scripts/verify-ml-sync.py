#!/usr/bin/env python3
"""Verify exact Mercado Livre correction fields in local and Railway MySQL."""
from __future__ import annotations

import json
import os
import subprocess
from decimal import Decimal
from pathlib import Path

MATCHES = Path("scripts/ml-sync-matches.json")
REPORT = Path("scripts/ml-sync-report.txt")
PRODUCT_FIELDS = ("quantity", "length", "width", "height", "weight")
REPORT_MARKER = "RUNTIME VERIFICATION"


def run(command: list[str], environment: dict[str, str] | None = None) -> str:
    return subprocess.check_output(command, text=True, stderr=subprocess.PIPE, env=environment)


def product_ids(matches: list[dict[str, object]]) -> str:
    return ",".join(str(match["product_id"]) for match in matches)


def query_local(ids: str) -> str:
    return run([
        "docker", "exec", "-i", "miigtools-ecommerce-mysql-1", "mysql",
        "-uroot", "-popencart", "opencart", "--default-character-set=utf8mb4",
        "-N", "--batch", "--raw", "-e",
        f"SELECT product_id, quantity, length, width, height, weight "
        f"FROM ws_product WHERE product_id IN ({ids}) ORDER BY product_id;",
    ])


def railway_variables() -> dict[str, str]:
    output = run(["npx", "--yes", "@railway/cli", "variables", "--service", "MySQL", "--kv"])
    values = {}
    for line in output.splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            values[key] = value
    required = ("RAILWAY_TCP_PROXY_DOMAIN", "RAILWAY_TCP_PROXY_PORT", "MYSQLUSER", "MYSQLPASSWORD", "MYSQLDATABASE")
    missing = [key for key in required if not values.get(key)]
    if missing:
        raise RuntimeError(f"Railway variables missing: {', '.join(missing)}")
    return values


def query_production(ids: str) -> str:
    values = railway_variables()
    environment = {**os.environ, "MYSQL_PWD": values["MYSQLPASSWORD"]}
    command = [
        "mysql", "-h", values["RAILWAY_TCP_PROXY_DOMAIN"], "-P", values["RAILWAY_TCP_PROXY_PORT"],
        "-u", values["MYSQLUSER"], values["MYSQLDATABASE"],
        "--default-character-set=utf8mb4", "-N", "--batch", "--raw", "-e",
        f"SELECT product_id, quantity, length, width, height, weight "
        f"FROM ws_product WHERE product_id IN ({ids}) ORDER BY product_id;",
    ]
    return run(command, environment)


def rows(output: str) -> dict[int, dict[str, str]]:
    result = {}
    for line in output.splitlines():
        values = line.split("\t")
        if len(values) != len(PRODUCT_FIELDS) + 1:
            raise ValueError(f"Unexpected MySQL row: {line!r}")
        result[int(values[0])] = dict(zip(PRODUCT_FIELDS, values[1:]))
    return result


def expected_fields(match: dict[str, object]) -> dict[str, str]:
    source = match["source"]
    expected = {"quantity": str(int(source["quantity"]))}
    for field in PRODUCT_FIELDS[1:]:
        if source[field] is not None:
            expected[field] = str(source[field])
    return expected


def verify(environment: str, output: str, matches: list[dict[str, object]]) -> dict[str, object]:
    actual_rows = rows(output)
    mismatches = []
    verified_shipping_fields = 0
    for match in matches:
        product_id = int(match["product_id"])
        actual = actual_rows.get(product_id)
        if actual is None:
            mismatches.append({"product_id": product_id, "field": "product", "expected": "present", "actual": "missing"})
            continue
        for field, expected in expected_fields(match).items():
            if field != "quantity":
                verified_shipping_fields += 1
            if Decimal(actual[field]) != Decimal(expected):
                mismatches.append({
                    "product_id": product_id,
                    "field": field,
                    "expected": expected,
                    "actual": actual[field],
                })
    return {
        "environment": environment,
        "matched_product_quantities_verified": len(matches),
        "dimensions_weights_populated_verified": verified_shipping_fields,
        "mismatches": mismatches,
        "all_fields_match": not mismatches,
    }


def update_report(verification: dict[str, object], multi_unit_listings_skipped: int) -> None:
    report = REPORT.read_text(encoding="utf-8")
    report = report.split(f"\n{REPORT_MARKER}\n", 1)[0].rstrip()
    local = verification["local"]
    production = verification["production"]
    report += (
        f"\n\n{REPORT_MARKER}\n"
        "- Verified against the exact spreadsheet source fields after the original correction.\n"
        "- Mapping: quantity=QUANTITY exactly; length=SHIPPING_DEPTH; width=SHIPPING_WIDTH; "
        "height=SHIPPING_HEIGHT; weight=SHIPPING_WEIGHT.\n"
        f"- Local: {local['matched_product_quantities_verified']} matched product quantities and "
        f"{local['dimensions_weights_populated_verified']} supplied dimension/weight fields verified; "
        f"{len(local['mismatches'])} mismatches.\n"
        f"- Production: {production['matched_product_quantities_verified']} matched product quantities and "
        f"{production['dimensions_weights_populated_verified']} supplied dimension/weight fields verified; "
        f"{len(production['mismatches'])} mismatches.\n"
        f"- Confirmed corrections applied by this verification: 0. Multi-unit listings skipped: {multi_unit_listings_skipped}.\n"
    )
    REPORT.write_text(report.rstrip() + "\n", encoding="utf-8")


def main() -> None:
    audit = json.loads(MATCHES.read_text(encoding="utf-8"))
    matches = audit["applied"]
    ids = product_ids(matches)
    verification = {
        "field_mapping": {
            "quantity": "QUANTITY exactly (sale-pack title counts do not multiply stock)",
            "length": "SHIPPING_DEPTH (cm)",
            "width": "SHIPPING_WIDTH (cm)",
            "height": "SHIPPING_HEIGHT (cm)",
            "weight": "SHIPPING_WEIGHT (kg)",
        },
        "local": verify("local", query_local(ids), matches),
        "production": verify("production", query_production(ids), matches),
        "confirmed_corrections_applied": 0,
        "multi_unit_listings_skipped": audit["summary"]["multi_unit_listings_skipped"],
    }
    audit["verification"] = verification
    MATCHES.write_text(json.dumps(audit, ensure_ascii=False, indent=2, default=dict) + "\n", encoding="utf-8")
    update_report(verification, verification["multi_unit_listings_skipped"])
    print(json.dumps(verification, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
