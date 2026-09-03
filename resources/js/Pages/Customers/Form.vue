<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import axios from 'axios';
import { t } from '@/i18n';

const props = defineProps({
  customer: Object,
  kvk_enabled: { type: Boolean, default: false },
});

// Markt (nl/pl): labels en placeholders voor KvK/REGON en btw-nummer/NIP.
const page = usePage();
const market = computed(() => page.props.market || {});
const isPolish = computed(() => market.value.key === 'pl');

const isEdit = computed(() => !!props.customer);

/* ---------- KvK-register zoeken (markt nl) ---------- */
const kvkQuery = ref('');
const kvkResults = ref([]);
const kvkSearching = ref(false);
const kvkFilling = ref(null);
const kvkError = ref(null);
const kvkSearched = ref(false);

const typeLabels = { hoofdvestiging: t('Hoofdvestiging'), nevenvestiging: t('Nevenvestiging'), rechtspersoon: t('Rechtspersoon') };

const kvkSearch = async () => {
  const q = kvkQuery.value.trim();
  if (q.length < 2 || kvkSearching.value) return;
  kvkSearching.value = true;
  kvkError.value = null;
  try {
    const { data } = await axios.get(route('kvk.search'), { params: { q } });
    kvkResults.value = data.results || [];
    kvkError.value = data.error || null;
    kvkSearched.value = true;
  } catch {
    kvkError.value = t('Zoeken mislukt — probeer het zo opnieuw.');
  } finally {
    kvkSearching.value = false;
  }
};

const kvkPick = async (result) => {
  kvkFilling.value = result.kvk_number;
  try {
    // Basisprofiel voor het volledige adres; lukt dat niet, dan gebruiken we
    // wat het zoekresultaat al weet (naam, straat, plaats).
    const { data } = await axios.get(route('kvk.profile', result.kvk_number));
    const p = data.result;

    form.name = (p?.name || result.name) ?? form.name;
    form.kvk_number = (p?.kvk_number || result.kvk_number) ?? form.kvk_number;
    form.address_line = p?.address_line ?? result.street ?? form.address_line;
    form.postal_code = p?.postal_code ?? form.postal_code;
    form.city = p?.city ?? result.city ?? form.city;
    form.country = 'NL';
    form.type = 'business';

    kvkResults.value = [];
    kvkQuery.value = '';
    kvkSearched.value = false;
  } catch {
    kvkError.value = t('Gegevens ophalen mislukt — vul het formulier handmatig in.');
  } finally {
    kvkFilling.value = null;
  }
};

/* ---------- NIP opzoeken in de "biała lista" (markt pl) ---------- */
const nipLoading = ref(false);
const nipError = ref(null);
const nipStatus = ref(null);

// Alleen de cijfers van wat er is ingetypt ("PL 123-456-78-90" → "1234567890").
const nipDigits = computed(() => String(form.vat_number ?? '').replace(/\D/g, ''));

const nipStatusLabels = {
  Czynny: t('Actieve btw-plichtige'),
  Zwolniony: t('Vrijgesteld van btw'),
  Niezarejestrowany: t('Niet geregistreerd voor btw'),
};
const nipStatusLabel = computed(() => nipStatusLabels[nipStatus.value] || nipStatus.value);

const nipLookup = async () => {
  const nip = nipDigits.value;
  if (nip.length !== 10 || nipLoading.value) return;
  nipLoading.value = true;
  nipError.value = null;
  nipStatus.value = null;
  try {
    const { data } = await axios.get(route('nip.lookup', nip));
    const r = data.result;
    if (data.error || !r) {
      nipError.value = data.error || t('Geen bedrijf gevonden op dit NIP — controleer het nummer of vul het formulier handmatig in.');
      return;
    }

    form.name = r.name || form.name;
    form.vat_number = r.nip || nip;
    form.kvk_number = r.regon ?? form.kvk_number;
    form.address_line = r.address ?? form.address_line;
    form.postal_code = r.postal_code ?? form.postal_code;
    form.city = r.city ?? form.city;
    form.country = 'PL';
    form.type = 'business';
    nipStatus.value = r.vat_status || null;
  } catch {
    nipError.value = t('Gegevens ophalen mislukt — vul het formulier handmatig in.');
  } finally {
    nipLoading.value = false;
  }
};

const form = useForm({
  name: props.customer?.name ?? '',
  type: props.customer?.type ?? 'business',
  contact_name: props.customer?.contact_name ?? '',
  email: props.customer?.email ?? '',
  phone: props.customer?.phone ?? '',
  kvk_number: props.customer?.kvk_number ?? '',
  vat_number: props.customer?.vat_number ?? '',
  peppol_id: props.customer?.peppol_id ?? '',
  address_line: props.customer?.address_line ?? '',
  postal_code: props.customer?.postal_code ?? '',
  city: props.customer?.city ?? '',
  country: props.customer?.country ?? (page.props.market?.country || 'NL'),
  language: props.customer?.language ?? (usePage().props.market?.locale || 'nl'),
  payment_terms: props.customer?.payment_terms ?? null,
  hourly_rate: props.customer?.hourly_rate ?? null,
  notes: props.customer?.notes ?? '',
  mandate_iban: props.customer?.mandate_iban ?? '',
  mandate_holder: props.customer?.mandate_holder ?? '',
  mandate_signed_on: props.customer?.mandate_signed_on ?? '',
  mandate_type: props.customer?.mandate_type ?? 'CORE',
  mandate_status: props.customer?.mandate_status ?? 'active',
  mandate_reference: props.customer?.mandate_reference ?? '',
  vvemaat_slug: props.customer?.vvemaat_slug ?? '',
});

const submit = () => {
  if (isEdit.value) {
    form.put(route('customers.update', props.customer.id));
  } else {
    form.post(route('customers.store'));
  }
};

const remove = () => {
  if (confirm(t('Klant ":name" verwijderen?', { name: props.customer.name }))) {
    router.delete(route('customers.destroy', props.customer.id));
  }
};
</script>

<template>
  <Head :title="isEdit ? $t('Klant :name', { name: customer.name }) : $t('Nieuwe klant')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Verkoop') }} / <Link :href="route('customers.index')" style="color:var(--text-3);">{{ $t('Klanten') }}</Link> /
        <span class="breadcrumb-current">{{ isEdit ? customer.name : $t('Nieuw') }}</span>
      </div>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('customers.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          {{ $t('Terug') }}
        </Link>
        <h1 class="page-title">{{ isEdit ? $t('Klant bewerken') : $t('Nieuwe klant') }}</h1>
      </div>
      <div class="page-actions">
        <button v-if="isEdit" class="btn btn-danger btn-sm" @click="remove">{{ $t('Verwijderen') }}</button>
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">
          {{ isEdit ? $t('Opslaan') : $t('Aanmaken') }}
        </button>
      </div>
    </div>

    <div class="single-col">
      <!-- Poolse markt: bedrijfsgegevens ophalen op NIP -->
      <div v-if="isPolish" class="card kvk-card">
        <div class="card-body">
          <div class="kvk-head">
            <span class="kvk-logo">NIP</span>
            <div>
              <div class="kvk-title">{{ $t('Zoek op NIP in het btw-register') }}</div>
              <div class="kvk-sub">{{ $t('Typ het NIP van het bedrijf — naam, adres en REGON worden automatisch ingevuld.') }}</div>
            </div>
          </div>
          <div class="kvk-search">
            <input
              type="text"
              v-model="form.vat_number"
              :placeholder="market.tax_id?.placeholder || '1234567890'"
              maxlength="20"
              inputmode="numeric"
              @keydown.enter.prevent="nipLookup"
            >
            <button type="button" class="btn btn-primary" :disabled="nipLoading || nipDigits.length !== 10" @click="nipLookup">
              {{ nipLoading ? $t('Ophalen…') : $t('Gegevens ophalen') }}
            </button>
          </div>
          <div v-if="nipError" class="field-error" style="margin-top:8px;">{{ nipError }}</div>
          <div v-else-if="nipStatus" class="kvk-empty">{{ $t('Btw-status') }}: <b>{{ nipStatusLabel }}</b> ({{ nipStatus }})</div>
        </div>
      </div>

      <!-- KvK-register zoeken (Nederlandse markt) -->
      <div v-else-if="kvk_enabled" class="card kvk-card">
        <div class="card-body">
          <div class="kvk-head">
            <span class="kvk-logo">KvK</span>
            <div>
              <div class="kvk-title">{{ $t('Zoek in het Handelsregister') }}</div>
              <div class="kvk-sub">{{ $t('Typ een bedrijfsnaam of KvK-nummer — kies het bedrijf en de gegevens worden ingevuld.') }}</div>
            </div>
          </div>
          <div class="kvk-search">
            <input
              type="text"
              v-model="kvkQuery"
              :placeholder="$t('Bijv. \'Bakkerij Janssen\' of 68750110')"
              maxlength="100"
              @keydown.enter.prevent="kvkSearch"
            >
            <button type="button" class="btn btn-primary" :disabled="kvkSearching || kvkQuery.trim().length < 2" @click="kvkSearch">
              {{ kvkSearching ? $t('Zoeken…') : $t('Zoeken') }}
            </button>
          </div>
          <div v-if="kvkError" class="field-error" style="margin-top:8px;">{{ kvkError }}</div>

          <div v-if="kvkResults.length" class="kvk-results">
            <button
              v-for="r in kvkResults"
              :key="r.kvk_number + (r.name || '')"
              type="button"
              class="kvk-result"
              :disabled="kvkFilling !== null"
              @click="kvkPick(r)"
            >
              <div class="kvk-result-main">
                <div class="kvk-result-name">{{ r.name }}</div>
                <div class="kvk-result-meta">
                  KvK {{ r.kvk_number }}<template v-if="r.city"> · {{ r.street ? r.street + ', ' : '' }}{{ r.city }}</template>
                </div>
              </div>
              <span class="kvk-type">{{ kvkFilling === r.kvk_number ? $t('Ophalen…') : (typeLabels[r.type] || r.type) }}</span>
            </button>
          </div>
          <div v-else-if="kvkSearched && !kvkSearching && !kvkError" class="kvk-empty">
            {{ $t('Geen bedrijven gevonden — controleer de spelling of vul het formulier handmatig in.') }}
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Algemeen') }}</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>{{ $t('Type') }} *</label>
            <div class="type-toggle">
              <button type="button" :class="['type-opt', { active: form.type === 'business' }]" @click="form.type = 'business'">
                {{ $t('Zakelijk') }}
              </button>
              <button type="button" :class="['type-opt', { active: form.type === 'consumer' }]" @click="form.type = 'consumer'">
                {{ $t('Particulier') }}
              </button>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ form.type === 'business' ? $t('Bedrijfsnaam') : $t('Naam') }} *</label>
              <input type="text" v-model="form.name" required maxlength="255">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group" v-if="form.type === 'business'">
              <label>{{ $t('Contactpersoon') }}</label>
              <input type="text" v-model="form.contact_name" maxlength="255">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('E-mail') }}</label>
              <input type="email" v-model="form.email" maxlength="255">
              <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Telefoon') }}</label>
              <input type="tel" v-model="form.phone" maxlength="50">
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;" v-if="form.type === 'business'">
        <div class="card-header"><div class="card-title">{{ $t('Zakelijk') }}</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ market.registry?.label || $t('KvK-nummer') }}</label>
              <input type="text" v-model="form.kvk_number" maxlength="20" :placeholder="market.registry?.placeholder || '12345678'">
            </div>
            <div class="form-group">
              <label>{{ market.tax_id?.label || $t('Btw-nummer') }}</label>
              <input type="text" v-model="form.vat_number" maxlength="20" :placeholder="market.tax_id?.placeholder || 'NL123456789B01'">
            </div>
          </div>
          <!-- Peppol geldt niet voor de Poolse markt (daar loopt e-facturatie via KSeF). -->
          <div class="form-row" v-if="!isPolish">
            <div class="form-group">
              <label>Peppol-ID<span class="muted" style="margin-left:6px;">{{ $t('(optioneel — standaard afgeleid van het KvK-nummer)') }}</span></label>
              <input type="text" v-model="form.peppol_id" maxlength="50" placeholder="0106:12345678">
              <div v-if="form.errors.peppol_id" class="field-error">{{ form.errors.peppol_id }}</div>
            </div>
            <div class="form-group" v-if="isEdit" style="display:flex;align-items:flex-end;">
              <button type="button" class="btn btn-secondary" style="width:100%;" @click="router.post(route('customers.peppol.check', customer.id), {}, { preserveScroll: true })">
                ⚡ {{ $t('Check Peppol-bereikbaarheid') }}
              </button>
            </div>
          </div>

          <!--
            Alleen voor klanten die een VvEMaat-omgeving afnemen. Aan dit veld
            hangt of een betaalde factuur wordt doorgegeven: staat het leeg, dan
            gebeurt er niets en blijft die vereniging op slot staan zodra haar
            proefperiode afloopt. Blijft leeg bij elke gewone klant.
          -->
          <div class="form-row" v-if="!isPolish">
            <div class="form-group">
              <label>{{ $t('VvEMaat-omgeving') }}<span class="muted" style="margin-left:6px;">{{ $t('(alleen voor VvE-klanten)') }}</span></label>
              <div style="display:flex;align-items:center;gap:6px;">
                <input type="text" v-model="form.vvemaat_slug" maxlength="63"
                       placeholder="keizersgracht214" style="flex:1;">
                <span class="muted" style="white-space:nowrap;">.vvemaat.nl</span>
              </div>
              <div v-if="form.errors.vvemaat_slug" class="field-error">{{ form.errors.vvemaat_slug }}</div>
              <div class="muted" style="font-size:12px;margin-top:4px;">
                {{ $t('Zodra een factuur van deze klant is voldaan, krijgt deze omgeving door tot wanneer er is betaald.') }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">{{ $t('Adres') }}</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>{{ $t('Adres') }}</label>
            <input type="text" v-model="form.address_line" maxlength="255" :placeholder="$t('Hoofdstraat 1')">
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label>{{ $t('Postcode') }}</label>
              <input type="text" v-model="form.postal_code" maxlength="20" :placeholder="$t('1234 AB')">
            </div>
            <div class="form-group">
              <label>{{ $t('Plaats') }}</label>
              <input type="text" v-model="form.city" maxlength="100">
            </div>
            <div class="form-group">
              <label>{{ $t('Land') }}</label>
              <select v-model="form.country">
                <option value="NL">{{ $t('Nederland') }}</option>
                <option value="BE">{{ $t('België') }}</option>
                <option value="DE">{{ $t('Duitsland') }}</option>
                <option value="FR">{{ $t('Frankrijk') }}</option>
                <option value="LU">{{ $t('Luxemburg') }}</option>
                <option value="PL">{{ $t('Polen') }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">{{ $t('Voorkeuren') }}</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>{{ $t('Taal van factuur & offerte') }}<span class="label-hint">{{ $t('(PDF en e-mail aan deze klant)') }}</span></label>
            <select v-model="form.language">
              <option value="nl">{{ $t('Nederlands') }}</option>
              <option value="en">{{ $t('Engels') }}</option>
              <option value="pl">{{ $t('Pools') }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>{{ $t('Betalingstermijn') }}<span class="label-hint">{{ $t('(laat leeg voor standaard van bedrijf)') }}</span></label>
            <input type="number" v-model="form.payment_terms" min="0" max="365" :placeholder="$t('Standaard')">
          </div>
          <div class="form-group">
            <label>{{ $t('Uurtarief') }}<span class="label-hint">{{ $t('(voor de urenregistratie — laat leeg voor standaard van bedrijf)') }}</span></label>
            <input type="number" v-model="form.hourly_rate" min="0" step="0.01" :placeholder="$t('Standaard')">
          </div>
          <div class="form-group" style="margin:0;">
            <label>{{ $t('Notities') }}<span class="label-hint">{{ $t('(intern, niet zichtbaar op factuur)') }}</span></label>
            <textarea v-model="form.notes" rows="3"></textarea>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Automatische incasso (SEPA)') }}</div>
            <div class="card-subtitle">{{ $t('Heeft deze klant je een machtiging gegeven? Vul het IBAN in; open facturen kun je dan in één batch bij je bank indienen.') }}</div>
          </div>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('IBAN van de klant') }}</label>
              <input type="text" v-model="form.mandate_iban" maxlength="40" class="mono" :placeholder="$t('NL00 BANK 0123 4567 89')">
              <div v-if="form.errors.mandate_iban" class="field-error">{{ form.errors.mandate_iban }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Naam rekeninghouder') }}<span class="label-hint">{{ $t('(leeg = klantnaam)') }}</span></label>
              <input type="text" v-model="form.mandate_holder" maxlength="70">
            </div>
          </div>
          <div class="form-row" v-if="form.mandate_iban">
            <div class="form-group">
              <label>{{ $t('Datum ondertekening machtiging') }}</label>
              <input type="date" v-model="form.mandate_signed_on">
            </div>
            <div class="form-group">
              <label>{{ $t('Soort machtiging') }}</label>
              <select v-model="form.mandate_type">
                <option value="CORE">{{ $t('Standaard (CORE) — particulier én zakelijk, 8 weken storneerbaar') }}</option>
                <option value="B2B">{{ $t('Zakelijk (B2B) — niet storneerbaar, klant registreert bij eigen bank') }}</option>
              </select>
            </div>
          </div>
          <div class="form-row" v-if="form.mandate_iban">
            <div class="form-group">
              <label>{{ $t('Machtigingskenmerk') }}<span class="label-hint">{{ $t('(leeg = automatisch)') }}</span></label>
              <input type="text" v-model="form.mandate_reference" maxlength="35" class="mono" :placeholder="$t('wordt automatisch aangemaakt')">
              <div v-if="form.errors.mandate_reference" class="field-error">{{ form.errors.mandate_reference }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Status') }}</label>
              <select v-model="form.mandate_status">
                <option value="active">{{ $t('Actief') }}</option>
                <option value="revoked">{{ $t('Ingetrokken') }}</option>
              </select>
            </div>
          </div>
          <div class="muted-hint">{{ $t('Bewaar de ondertekende machtiging zelf (papier of PDF); de bank kan daar bij een storno naar vragen.') }}</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.single-col { max-width: 760px; }
.muted-hint { font-size: 12px; color: var(--text-4); line-height: 1.6; margin-top: 4px; }
.type-toggle {
  display: inline-flex;
  background: var(--surface-2);
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}
.type-opt {
  padding: 7px 16px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-3);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
}
.type-opt:hover:not(.active) { color: var(--text); }
.type-opt.active { background: var(--surface); color: var(--text); box-shadow: var(--shadow-sm); }

/* KvK-zoeker */
.kvk-card { margin-bottom: 16px; border-color: var(--info-border); }
.kvk-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.kvk-logo {
  width: 40px; height: 40px; border-radius: 9px; flex: none;
  background: #21145F; color: #fff;
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-weight: 700; font-size: 13px;
}
.kvk-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; }
.kvk-sub { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }
.kvk-search { display: flex; gap: 8px; }
.kvk-search input { flex: 1; }
.kvk-results { margin-top: 10px; display: flex; flex-direction: column; gap: 6px; }
.kvk-result {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  width: 100%; text-align: left;
  border: 1px solid var(--border); border-radius: 9px;
  padding: 10px 14px; background: var(--surface);
  transition: border-color 0.15s, background 0.15s;
}
.kvk-result:hover:not(:disabled) { border-color: var(--brand); background: var(--brand-tint); }
.kvk-result:disabled { opacity: 0.6; cursor: wait; }
.kvk-result-name { font-weight: 600; font-size: 13.5px; }
.kvk-result-meta { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.kvk-type {
  flex: none; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  background: var(--surface-2); color: var(--text-3);
  border: 1px solid var(--border-strong); border-radius: 100px; padding: 3px 9px;
}
.kvk-empty { margin-top: 10px; font-size: 12.5px; color: var(--text-3); }
</style>
