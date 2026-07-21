<?php
/**
 * Expand industrial search synonyms for MIIGTOOLS catalog.
 */
function miig_expand_search_query(string $query): string {
	$query = trim(preg_replace('/\s+/u', ' ', $query) ?? $query);

	if ($query === '') {
		return '';
	}

	$replacements = [
		'/\btaps?\b/iu'                 => 'macho',
		'/\btapping\b/iu'               => 'macho',
		'/\bcone\s*morse\b/iu'          => 'CM',
		'/\bmorse\s*taper\b/iu'         => 'CM',
		'/\blive\s*center\b/iu'         => 'ponta rotativa',
		'/\brevolving\s*center\b/iu'    => 'ponta rotativa',
		'/\bdead\s*center\b/iu'         => 'ponta rotativa',
		'/\bknurl(ing)?\s*holder\b/iu'  => 'porta recartilha',
		'/\bknurl(ing)?\b/iu'           => 'recartilha',
		'/\btool\s*bit\b/iu'            => 'bits',
		'/\bhigh\s*speed\s*steel\b/iu'  => 'HSS',
		'/\bcobalt\b/iu'                => 'Co',
	];

	$expanded = $query;

	foreach ($replacements as $pattern => $replacement) {
		$expanded = preg_replace($pattern, $replacement, $expanded) ?? $expanded;
	}

	// Normalize "cm 3" / "cm3" → "CM3"
	$expanded = preg_replace('/\bcm\s*(\d+)\b/iu', 'CM$1', $expanded) ?? $expanded;

	return trim(preg_replace('/\s+/u', ' ', $expanded) ?? $expanded);
}
