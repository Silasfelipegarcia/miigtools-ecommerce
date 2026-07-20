#!/usr/bin/env python3
"""Atualiza a Política de Privacidade (information_id=3) no MySQL local e/ou Railway."""

from __future__ import annotations

import argparse
import os
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PT_FILE = ROOT / "scripts" / "privacy-policy-pt-br.html"
EN_FILE = ROOT / "scripts" / "privacy-policy-en-gb.html"
INFORMATION_ID = 3


def run(cmd: list[str], *, env: dict[str, str] | None = None, input_text: str | None = None) -> str:
    result = subprocess.run(
        cmd,
        check=True,
        capture_output=True,
        text=True,
        env=env,
        input=input_text,
    )
    return result.stdout


def mysql_escape(value: str) -> str:
    return (
        value.replace("\\", "\\\\")
        .replace("'", "\\'")
        .replace("\x00", "")
    )


def build_sql(pt_html: str, en_html: str) -> str:
    pt = mysql_escape(pt_html)
    en = mysql_escape(en_html)
    return f"""
UPDATE ws_information SET status = 1, sort_order = 4 WHERE information_id = {INFORMATION_ID};

INSERT INTO ws_information_description
  (information_id, language_id, title, description, meta_title, meta_description, meta_keyword)
VALUES
  ({INFORMATION_ID}, 2, 'Política de Privacidade', '{pt}',
   'Política de Privacidade | MIIGTOOLS',
   'Política de Privacidade da MIIGTOOLS conforme a LGPD (Lei 13.709/2018): dados coletados, finalidades, bases legais, cookies, compartilhamento e direitos do titular.',
   'privacidade, LGPD, proteção de dados, cookies, MIIGTOOLS'),
  ({INFORMATION_ID}, 1, 'Privacy Policy', '{en}',
   'Privacy Policy | MIIGTOOLS',
   'MIIGTOOLS Privacy Policy under Brazil LGPD: data collected, purposes, legal bases, cookies, sharing and data subject rights.',
   'privacy, LGPD, data protection, cookies, MIIGTOOLS')
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  description = VALUES(description),
  meta_title = VALUES(meta_title),
  meta_description = VALUES(meta_description),
  meta_keyword = VALUES(meta_keyword);

INSERT IGNORE INTO ws_information_to_store (information_id, store_id) VALUES ({INFORMATION_ID}, 0);

UPDATE ws_setting SET value = '{INFORMATION_ID}', serialized = 0
WHERE `key` = 'config_gdpr_id' AND store_id = 0;

UPDATE ws_setting SET value = '{INFORMATION_ID}', serialized = 0
WHERE `key` = 'config_cookie_id' AND store_id = 0;

INSERT INTO ws_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'config', 'config_gdpr_id', '{INFORMATION_ID}', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ws_setting WHERE `key` = 'config_gdpr_id' AND store_id = 0
);

INSERT INTO ws_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'config', 'config_cookie_id', '{INFORMATION_ID}', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ws_setting WHERE `key` = 'config_cookie_id' AND store_id = 0
);

SELECT information_id, language_id, title, CHAR_LENGTH(description) AS chars
FROM ws_information_description
WHERE information_id = {INFORMATION_ID}
ORDER BY language_id;

SELECT `key`, value FROM ws_setting
WHERE `key` IN ('config_gdpr_id', 'config_cookie_id', 'config_account_id') AND store_id = 0;
"""


def update_local(sql: str) -> None:
    print("→ Atualizando MySQL local (Docker)...")
    run(
        [
            "docker",
            "compose",
            "exec",
            "-T",
            "mysql",
            "mysql",
            "-uroot",
            "-popencart",
            "opencart",
        ],
        input_text=sql,
        env=os.environ.copy(),
    )
    print("  OK local")


def railway_variables() -> dict[str, str]:
    output = run(["npx", "--yes", "@railway/cli", "variables", "--service", "MySQL", "--kv"])
    values: dict[str, str] = {}
    for line in output.splitlines():
        if "=" not in line:
            continue
        key, value = line.split("=", 1)
        values[key] = value
    required = (
        "RAILWAY_TCP_PROXY_DOMAIN",
        "RAILWAY_TCP_PROXY_PORT",
        "MYSQLUSER",
        "MYSQLPASSWORD",
        "MYSQLDATABASE",
    )
    missing = [key for key in required if not values.get(key)]
    if missing:
        raise RuntimeError(f"Variáveis Railway ausentes: {', '.join(missing)}")
    return values


def update_railway(sql: str) -> None:
    print("→ Atualizando MySQL Railway...")
    values = railway_variables()
    env = {**os.environ, "MYSQL_PWD": values["MYSQLPASSWORD"]}
    out = run(
        [
            "mysql",
            "-h",
            values["RAILWAY_TCP_PROXY_DOMAIN"],
            "-P",
            values["RAILWAY_TCP_PROXY_PORT"],
            "-u",
            values["MYSQLUSER"],
            values["MYSQLDATABASE"],
        ],
        input_text=sql,
        env=env,
    )
    print(out)
    print("  OK Railway")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--local", action="store_true", help="Atualiza apenas o MySQL local")
    parser.add_argument("--railway", action="store_true", help="Atualiza apenas o MySQL do Railway")
    parser.add_argument("--both", action="store_true", help="Atualiza local e Railway")
    args = parser.parse_args()

    if not args.local and not args.railway and not args.both:
        args.both = True

    pt_html = PT_FILE.read_text(encoding="utf-8").strip()
    en_html = EN_FILE.read_text(encoding="utf-8").strip()
    sql = build_sql(pt_html, en_html)

    try:
        if args.local or args.both:
            update_local(sql)
        if args.railway or args.both:
            update_railway(sql)
    except subprocess.CalledProcessError as exc:
        print(exc.stderr or exc.stdout or str(exc), file=sys.stderr)
        return 1
    except Exception as exc:  # noqa: BLE001
        print(str(exc), file=sys.stderr)
        return 1

    print("Política de Privacidade publicada (information_id=3).")
    print("Local:  http://localhost:8888/index.php?route=information/information&language=pt-br&information_id=3")
    print("Prod:   https://miigtools-ecommerce-production.up.railway.app/index.php?route=information/information&language=pt-br&information_id=3")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
