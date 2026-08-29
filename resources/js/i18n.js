/**
 * Lichte vertaallaag voor de Vue-kant. De Nederlandse brontekst is de sleutel;
 * per taal ligt er een woordenboek in resources/js/lang/<taal>/*.json (meerdere
 * bestanden per taal, die hier worden samengevoegd). Ontbreekt een vertaling,
 * dan blijft de Nederlandse tekst staan — zo breekt er nooit iets.
 *
 * Gebruik in templates: {{ $t('Facturen') }}, :placeholder="$t('Zoek op klant…')"
 * en met plaatsvervangers: $t('Nog :n dagen', { n: 3 }).
 */
const dictionaries = {};

// Vite bundelt alle json-bestanden onder lang/<taal>/ mee.
const files = import.meta.glob('./lang/*/*.json', { eager: true });
for (const [path, mod] of Object.entries(files)) {
  const lang = path.split('/')[2];
  dictionaries[lang] = Object.assign(dictionaries[lang] || {}, mod.default || mod);
}

let current = (typeof document !== 'undefined' && document.documentElement.dataset.locale) || 'nl';

export function setLocale(locale) {
  current = locale || 'nl';
}

export function locale() {
  return current;
}

export function t(text, params) {
  const dict = dictionaries[current];
  let out = (dict && Object.prototype.hasOwnProperty.call(dict, text)) ? dict[text] : text;
  if (params) {
    for (const [key, value] of Object.entries(params)) {
      out = out.split(':' + key).join(String(value));
    }
  }
  return out;
}

export default { t, setLocale, locale };
