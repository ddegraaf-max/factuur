<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';
import { eur, num } from '@/format';

// Markt (nl/pl): KvK/REGON, btw/NIP, land, kilometertarief, betaalmethode.
const market = usePage().props.market;
const formatExample = market.key === 'pl' ? 'FV/{year}/{sequence:4}' : '{year}-{sequence:4}';

// Landkeuze: het land van de markt bovenaan, daarna de buurlanden.
const countries = [
  { code: 'NL', label: t('Nederland') },
  { code: 'BE', label: t('België') },
  { code: 'DE', label: t('Duitsland') },
  { code: 'PL', label: t('Polen') },
].sort((a, b) => (a.code === market.country ? -1 : b.code === market.country ? 1 : 0));

// AVG: administratie definitief verwijderen — wachtwoord + bedrijfsnaam als dubbele bevestiging.
const destroyCompany = () => {
  const confirm = prompt(t('Dit verwijdert de administratie ":name" met alle facturen, offertes, klanten en gebruikers — definitief.', { name: props.company.name }) + '\n\n' + t('Typ ter bevestiging de bedrijfsnaam:'));
  if (confirm === null) return;
  const password = prompt(t('Bevestig met je wachtwoord:'));
  if (password === null) return;
  router.delete(route('settings.company.destroy'), { data: { confirm, password } });
};

const props = defineProps({
  company: Object,
  mollie_connected: Boolean, // is er een Mollie-koppeling? (de key zelf blijft geheim)
  storage: Object,           // { used_bytes, limit_bytes, percent, used_label, limit_label, full }
});

const form = useForm({
  name: props.company.name ?? '',
  trading_name: props.company.trading_name ?? '',
  kvk_number: props.company.kvk_number ?? '',
  vat_number: props.company.vat_number ?? '',
  iban: props.company.iban ?? '',
  sepa_creditor_id: props.company.sepa_creditor_id ?? '',
  email: props.company.email ?? '',
  copy_email: props.company.copy_email ?? '',
  accountant_email: props.company.accountant_email ?? '',
  phone: props.company.phone ?? '',
  website: props.company.website ?? '',
  address_line: props.company.address_line ?? '',
  postal_code: props.company.postal_code ?? '',
  city: props.company.city ?? '',
  country: props.company.country ?? market.country,
  brand_color: props.company.brand_color ?? '#E8231F',
  default_payment_terms: props.company.default_payment_terms ?? 30,
  default_hourly_rate: props.company.default_hourly_rate ?? null,
  default_km_rate: props.company.default_km_rate ?? market.km_rate,
  mollie_api_key: '',
  mollie_disconnect: false,
  invoice_footer: props.company.invoice_footer ?? '',
  invoice_number_format: props.company.invoice_number_format ?? formatExample,
  price_mode: props.company.price_mode ?? 'excl',
  daily_notification_enabled: !!props.company.daily_notification_enabled,
  daily_notification_email: props.company.daily_notification_email ?? '',
});

const submit = () => form.patch(route('settings.company.update'));
</script>

<template>
  <Head :title="$t('Bedrijfsgegevens')" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">{{ $t('Instellingen') }} / <span class="breadcrumb-current">{{ $t('Bedrijfsgegevens') }}</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Bedrijfsgegevens') }}</h1>
        <p class="page-subtitle">{{ $t('Deze gegevens verschijnen op al je facturen') }}</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">{{ $t('Opslaan') }}</button>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">{{ $t('Bedrijf') }}</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Bedrijfsnaam') }} *</label>
              <input type="text" v-model="form.name" required maxlength="255">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group">
              <label>{{ $t('Handelsnaam') }}<span class="label-hint">{{ $t('(optioneel)') }}</span></label>
              <input type="text" v-model="form.trading_name" maxlength="255">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ market.registry.label }}</label>
              <input type="text" v-model="form.kvk_number" maxlength="20" :placeholder="market.registry.placeholder">
            </div>
            <div class="form-group">
              <label>{{ market.tax_id.label }}</label>
              <input type="text" v-model="form.vat_number" maxlength="20" :placeholder="market.tax_id.placeholder">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>IBAN<span class="label-hint">{{ $t('(verschijnt op facturen)') }}</span></label>
              <input type="text" v-model="form.iban" maxlength="34" class="mono">
            </div>
            <div class="form-group">
              <label>{{ $t('Incassant-ID') }}<span class="label-hint">{{ $t('(voor automatische incasso, bijv. NL12ZZZ123456780000)') }}</span></label>
              <input type="text" v-model="form.sepa_creditor_id" maxlength="35" class="mono" :placeholder="$t('Vraag aan bij je bank')">
              <div v-if="form.errors.sepa_creditor_id" class="field-error">{{ form.errors.sepa_creditor_id }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">{{ $t('Contact') }}</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('E-mail') }}</label>
              <input type="email" v-model="form.email" maxlength="255">
            </div>
            <div class="form-group">
              <label>{{ $t('Telefoon') }}</label>
              <input type="tel" v-model="form.phone" maxlength="50">
            </div>
          </div>
          <div class="form-group" style="margin:0;">
            <label>{{ $t('Website') }}</label>
            <input type="url" v-model="form.website" maxlength="255" placeholder="https://...">
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">{{ $t('Facturen versturen') }}</div></div>
        <div class="card-body">
          <p style="font-size:13px;color:var(--text-3);line-height:1.6;margin:0 0 14px;">
            {{ $t('Wanneer je een factuur verstuurt, gaat deze naar de klant. Hieronder stel je in wie automatisch een kopie ontvangt: jijzelf (CC) en je boekhoudkantoor (BCC, onzichtbaar voor de klant).') }}
          </p>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Kopie naar jezelf (CC)') }}</label>
              <input type="email" v-model="form.copy_email" maxlength="255" :placeholder="$t('jij@bedrijf.nl')">
              <div v-if="form.errors.copy_email" class="field-error">{{ form.errors.copy_email }}</div>
              <div class="muted">{{ $t('Leeg = je bedrijfs-e-mailadres.') }}</div>
            </div>
            <div class="form-group" style="margin:0;">
              <label>{{ $t('Boekhoudkantoor (BCC)') }}</label>
              <input type="email" v-model="form.accountant_email" maxlength="255" :placeholder="$t('administratie@kantoor.nl')">
              <div v-if="form.errors.accountant_email" class="field-error">{{ form.errors.accountant_email }}</div>
              <div class="muted">{{ $t('Onzichtbaar voor de klant.') }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">{{ $t('Adres') }}</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>{{ $t('Adres') }}</label>
            <input type="text" v-model="form.address_line" maxlength="255">
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label>{{ $t('Postcode') }}</label>
              <input type="text" v-model="form.postal_code" maxlength="20">
            </div>
            <div class="form-group">
              <label>{{ $t('Plaats') }}</label>
              <input type="text" v-model="form.city" maxlength="100">
            </div>
            <div class="form-group">
              <label>{{ $t('Land') }} *</label>
              <select v-model="form.country">
                <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.label }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">{{ $t('Factuurinstellingen') }}</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Standaard betalingstermijn (dagen)') }} *</label>
              <input type="number" v-model.number="form.default_payment_terms" min="0" max="365" required>
            </div>
            <div class="form-group">
              <label>{{ $t('Factuurnummer-formaat') }} *</label>
              <input type="text" v-model="form.invoice_number_format" required maxlength="50" class="mono" :placeholder="formatExample">
              <div style="font-size:11px;color:var(--text-4);margin-top:4px;">
                {{ $t('Gebruik') }} <code style="font-family:var(--font-mono);">{year}</code> {{ $t('en') }} <code style="font-family:var(--font-mono);">{sequence:4}</code> — {{ $t('bijv.') }} <code style="font-family:var(--font-mono);">{{ formatExample }}</code>
              </div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ $t('Standaard uurtarief') }}<span class="label-hint">{{ $t('(urenregistratie — per klant of regel te overschrijven)') }}</span></label>
              <input type="number" v-model="form.default_hourly_rate" min="0" step="0.01" :placeholder="$t('Bijv. :amount', { amount: num(75) })">
            </div>
            <div class="form-group">
              <label>{{ $t('Kilometervergoeding') }}<span class="label-hint">{{ $t('(per km — :rate is het onbelaste tarief)', { rate: eur(market.km_rate) }) }}</span></label>
              <input type="number" v-model="form.default_km_rate" min="0" step="0.01" :placeholder="num(market.km_rate)">
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Huisstijlkleur') }}</label>
            <div style="display:flex;gap:10px;align-items:center;">
              <input type="color" v-model="form.brand_color" style="width:48px;height:42px;padding:2px;cursor:pointer;">
              <input type="text" v-model="form.brand_color" maxlength="7" class="mono" style="width:120px;">
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Prijzen invoeren') }}</label>
            <select v-model="form.price_mode">
              <option value="excl">{{ $t('Exclusief btw — ik typ nettobedragen') }}</option>
              <option value="incl">{{ $t('Inclusief btw — ik typ de prijs die de klant betaalt') }}</option>
            </select>
            <div style="font-size:11px;color:var(--text-4);margin-top:4px;">
              {{ $t('Handig voor webshops, horeca en andere particuliere verkoop. De factuur zelf toont altijd netto, btw én totaal — dat is wettelijk verplicht.') }}
            </div>
          </div>
          <div class="form-group">
            <label>{{ $t('Online betalingen — :method via Mollie', { method: market.online_payment_label }) }}<span class="label-hint">{{ $t('(betaallink in de factuurmail en het klantenportaal)') }}</span></label>
            <div v-if="mollie_connected" class="mollie-status" :class="{ 'is-removing': form.mollie_disconnect }">
              <template v-if="!form.mollie_disconnect">
                <span class="mollie-status-text">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  {{ $t('Mollie gekoppeld — klanten kunnen online betalen') }}
                </span>
                <button type="button" class="btn btn-secondary btn-sm" @click="form.mollie_disconnect = true">{{ $t('Koppeling verwijderen') }}</button>
              </template>
              <template v-else>
                <span class="mollie-status-text">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  {{ $t('De koppeling wordt verwijderd zodra je opslaat — klanten kunnen dan niet meer online betalen.') }}
                </span>
                <button type="button" class="btn btn-secondary btn-sm" @click="form.mollie_disconnect = false">{{ $t('Toch behouden') }}</button>
              </template>
            </div>
            <template v-else>
              <input type="password" v-model="form.mollie_api_key" :placeholder="$t('live_... of test_...')" autocomplete="off" style="max-width:340px;">
              <div style="font-size:11px;color:var(--text-4);margin-top:4px;line-height:1.6;">
                {{ $t('Plak hier de') }} <b>{{ $t('Live API-sleutel') }}</b> {{ $t('van je eigen Mollie-account (mollie.com → Developers → API-toegangstokens).') }}
                {{ $t('Betalingen gaan rechtstreeks naar jouw rekening; wij zitten er niet tussen.') }}
              </div>
            </template>
            <div v-if="form.errors.mollie_api_key" class="field-error">{{ form.errors.mollie_api_key }}</div>
          </div>
          <div class="form-group" style="margin:0;">
            <label>{{ $t('Standaard voetnoot') }}<span class="label-hint">{{ $t('(onderaan elke factuur)') }}</span></label>
            <textarea v-model="form.invoice_footer" rows="3" :placeholder="$t('Bijv. betalingsvoorwaarden, BTW-mededelingen...')"></textarea>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Dagelijks overzicht') }}</div>
            <div class="card-subtitle">{{ $t('Elke ochtend een mailtje met wat er die dag om aandacht vraagt') }}</div>
          </div>
        </div>
        <div class="card-body">
          <label class="toggle-row">
            <input type="checkbox" v-model="form.daily_notification_enabled">
            <div>
              <div class="toggle-title">{{ $t('Stuur mij elke ochtend een overzicht') }}</div>
              <div class="toggle-sub">
                {{ $t('Vervallen facturen, betalingen van gisteren, wat er binnen een week vervalt en concepten die nog klaarstaan. Is er niets te melden, dan krijg je ook geen mail.') }}
              </div>
            </div>
          </label>

          <div class="form-group" style="margin:16px 0 0;" v-if="form.daily_notification_enabled">
            <label>{{ $t('Stuur naar') }}<span class="label-hint">{{ $t('(leeg = je bedrijfs-e-mailadres)') }}</span></label>
            <input type="email" v-model="form.daily_notification_email" maxlength="255" :placeholder="$t('jij@bedrijf.nl')">
            <div v-if="form.errors.daily_notification_email" class="field-error">{{ form.errors.daily_notification_email }}</div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header">
          <div>
            <div class="card-title">{{ $t('Jouw gegevens') }}</div>
            <div class="card-subtitle">{{ $t('Alles wat in deze administratie staat is van jou — exporteer het wanneer je wilt, of verwijder de administratie definitief (AVG)') }}</div>
          </div>
        </div>
        <div class="card-body">
          <div class="data-row" v-if="storage">
            <div style="flex:1;">
              <div class="toggle-title">{{ $t('Opslag: :used van :limit', { used: storage.used_label, limit: storage.limit_label }) }}</div>
              <div class="storage-bar"><div class="storage-fill" :class="{ warn: storage.percent > 80, full: storage.full }" :style="{ width: Math.max(1, storage.percent) + '%' }"></div></div>
              <div class="toggle-sub">{{ $t('Bijlagen bij facturen, offertes en inkoop, plus bonnetjes in het Postvak IN en je logo.') }} {{ storage.full ? $t('De opslag is vol — verwijder oude bijlagen of stap over op Slim (10 GB).') : $t('Ruim voldoende voor jaren aan PDF\'s en bonnen; Slim heeft 10 GB.') }}</div>
            </div>
          </div>
          <div class="data-row">
            <div>
              <div class="toggle-title">{{ $t('Volledige export') }}</div>
              <div class="toggle-sub">{{ $t('ZIP met klanten, producten, facturen (incl. regels en betalingen), offertes, inkoopfacturen, uren en ritten als CSV én JSON. Handig voor je eigen archief of een overstap.') }}</div>
            </div>
            <a :href="route('settings.data.export')" class="btn btn-secondary btn-sm">{{ $t('Exporteer ZIP') }}</a>
          </div>
          <div class="data-row" v-if="$page.props.auth.can?.settings">
            <div>
              <div class="toggle-title">{{ $t('Administratie verwijderen') }}</div>
              <div class="toggle-sub">{{ $t('Wist alle gegevens van deze administratie direct en definitief, inclusief de gebruikersaccounts van je team. Denk aan de fiscale bewaarplicht van 7 jaar: exporteer eerst.') }}</div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm btn-danger-ghost" @click="destroyCompany">{{ $t('Definitief verwijderen') }}</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.data-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; border-top: 1px solid var(--border); }
.data-row:first-child { border-top: none; padding-top: 0; }
.data-row .toggle-sub { max-width: 620px; }
.storage-bar { height: 8px; border-radius: 100px; background: var(--surface-3); overflow: hidden; margin: 8px 0 6px; max-width: 420px; }
.storage-fill { height: 100%; background: var(--success); border-radius: 100px; transition: width .3s; }
.storage-fill.warn { background: var(--warning); }
.storage-fill.full { background: var(--brand); }
.btn-danger-ghost { color: #B91C1C; border-color: #FECACA; flex: none; }
.btn-danger-ghost:hover { background: #FEF2F2; }
.mollie-status {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  padding: 10px 14px; border: 1px solid var(--success-border); border-radius: var(--r-sm); background: var(--success-bg);
}
.mollie-status-text { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--success); line-height: 1.4; }
.mollie-status.is-removing { border-color: var(--danger-border, #FECACA); background: var(--danger-bg, #FEF2F2); }
.mollie-status.is-removing .mollie-status-text { color: var(--danger, #B91C1C); font-weight: 500; }
.toggle-row { display: flex; gap: 12px; align-items: flex-start; cursor: pointer; }
.toggle-row input { width: 18px; height: 18px; margin-top: 2px; accent-color: var(--brand); flex: none; }
.toggle-title { font-weight: 600; font-size: 14px; }
.toggle-sub { font-size: 12.5px; color: var(--text-3); margin-top: 3px; line-height: 1.55; }
</style>
