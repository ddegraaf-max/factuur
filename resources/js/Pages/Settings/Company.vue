<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  company: Object,
  mollie_connected: Boolean, // is er een Mollie-koppeling? (de key zelf blijft geheim)
});

const form = useForm({
  name: props.company.name ?? '',
  trading_name: props.company.trading_name ?? '',
  kvk_number: props.company.kvk_number ?? '',
  vat_number: props.company.vat_number ?? '',
  iban: props.company.iban ?? '',
  email: props.company.email ?? '',
  copy_email: props.company.copy_email ?? '',
  accountant_email: props.company.accountant_email ?? '',
  phone: props.company.phone ?? '',
  website: props.company.website ?? '',
  address_line: props.company.address_line ?? '',
  postal_code: props.company.postal_code ?? '',
  city: props.company.city ?? '',
  country: props.company.country ?? 'NL',
  brand_color: props.company.brand_color ?? '#E8231F',
  default_payment_terms: props.company.default_payment_terms ?? 30,
  default_hourly_rate: props.company.default_hourly_rate ?? null,
  default_km_rate: props.company.default_km_rate ?? 0.23,
  mollie_api_key: '',
  mollie_disconnect: false,
  invoice_footer: props.company.invoice_footer ?? '',
  invoice_number_format: props.company.invoice_number_format ?? '{year}-{sequence:4}',
  price_mode: props.company.price_mode ?? 'excl',
  daily_notification_enabled: !!props.company.daily_notification_enabled,
  daily_notification_email: props.company.daily_notification_email ?? '',
});

const submit = () => form.patch(route('settings.company.update'));
</script>

<template>
  <Head title="Bedrijfsgegevens" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Instellingen / <span class="breadcrumb-current">Bedrijfsgegevens</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Bedrijfsgegevens</h1>
        <p class="page-subtitle">Deze gegevens verschijnen op al je facturen</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">Opslaan</button>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">Bedrijf</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Bedrijfsnaam *</label>
              <input type="text" v-model="form.name" required maxlength="255">
              <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
            </div>
            <div class="form-group">
              <label>Handelsnaam<span class="label-hint">(optioneel)</span></label>
              <input type="text" v-model="form.trading_name" maxlength="255">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>KVK-nummer</label>
              <input type="text" v-model="form.kvk_number" maxlength="20">
            </div>
            <div class="form-group">
              <label>BTW-nummer</label>
              <input type="text" v-model="form.vat_number" maxlength="20">
            </div>
          </div>
          <div class="form-group">
            <label>IBAN<span class="label-hint">(verschijnt op facturen)</span></label>
            <input type="text" v-model="form.iban" maxlength="34" class="mono">
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Contact</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>E-mail</label>
              <input type="email" v-model="form.email" maxlength="255">
            </div>
            <div class="form-group">
              <label>Telefoon</label>
              <input type="tel" v-model="form.phone" maxlength="50">
            </div>
          </div>
          <div class="form-group" style="margin:0;">
            <label>Website</label>
            <input type="url" v-model="form.website" maxlength="255" placeholder="https://...">
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Facturen versturen</div></div>
        <div class="card-body">
          <p style="font-size:13px;color:var(--text-3);line-height:1.6;margin:0 0 14px;">
            Wanneer je een factuur verstuurt, gaat deze naar de klant. Hieronder stel je in wie
            automatisch een kopie ontvangt: jijzelf (CC) en je boekhoudkantoor (BCC, onzichtbaar voor de klant).
          </p>
          <div class="form-row">
            <div class="form-group">
              <label>Kopie naar jezelf (CC)</label>
              <input type="email" v-model="form.copy_email" maxlength="255" placeholder="jij@bedrijf.nl">
              <div v-if="form.errors.copy_email" class="field-error">{{ form.errors.copy_email }}</div>
              <div class="muted">Leeg = je bedrijfs-e-mailadres.</div>
            </div>
            <div class="form-group" style="margin:0;">
              <label>Boekhoudkantoor (BCC)</label>
              <input type="email" v-model="form.accountant_email" maxlength="255" placeholder="administratie@kantoor.nl">
              <div v-if="form.errors.accountant_email" class="field-error">{{ form.errors.accountant_email }}</div>
              <div class="muted">Onzichtbaar voor de klant.</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Adres</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Adres</label>
            <input type="text" v-model="form.address_line" maxlength="255">
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label>Postcode</label>
              <input type="text" v-model="form.postal_code" maxlength="20">
            </div>
            <div class="form-group">
              <label>Plaats</label>
              <input type="text" v-model="form.city" maxlength="100">
            </div>
            <div class="form-group">
              <label>Land *</label>
              <select v-model="form.country">
                <option value="NL">Nederland</option>
                <option value="BE">België</option>
                <option value="DE">Duitsland</option>
                <option value="PL">Polen</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Factuurinstellingen</div></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Standaard betalingstermijn (dagen) *</label>
              <input type="number" v-model.number="form.default_payment_terms" min="0" max="365" required>
            </div>
            <div class="form-group">
              <label>Factuurnummer-formaat *</label>
              <input type="text" v-model="form.invoice_number_format" required maxlength="50" class="mono">
              <div style="font-size:11px;color:var(--text-4);margin-top:4px;">
                Gebruik <code style="font-family:var(--font-mono);">{year}</code> en <code style="font-family:var(--font-mono);">{sequence:4}</code>
              </div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Standaard uurtarief<span class="label-hint">(urenregistratie — per klant of regel te overschrijven)</span></label>
              <input type="number" v-model="form.default_hourly_rate" min="0" step="0.01" placeholder="Bijv. 75,00">
            </div>
            <div class="form-group">
              <label>Kilometervergoeding<span class="label-hint">(per km — € 0,23 is het onbelaste tarief)</span></label>
              <input type="number" v-model="form.default_km_rate" min="0" step="0.01" placeholder="0,23">
            </div>
          </div>
          <div class="form-group">
            <label>Huisstijlkleur</label>
            <div style="display:flex;gap:10px;align-items:center;">
              <input type="color" v-model="form.brand_color" style="width:48px;height:42px;padding:2px;cursor:pointer;">
              <input type="text" v-model="form.brand_color" maxlength="7" class="mono" style="width:120px;">
            </div>
          </div>
          <div class="form-group">
            <label>Prijzen invoeren</label>
            <select v-model="form.price_mode">
              <option value="excl">Exclusief btw — ik typ nettobedragen</option>
              <option value="incl">Inclusief btw — ik typ de prijs die de klant betaalt</option>
            </select>
            <div style="font-size:11px;color:var(--text-4);margin-top:4px;">
              Handig voor webshops, horeca en andere particuliere verkoop. De factuur zelf toont altijd
              netto, btw én totaal — dat is wettelijk verplicht.
            </div>
          </div>
          <div class="form-group">
            <label>Online betalingen — iDEAL via Mollie<span class="label-hint">(betaallink in de factuurmail en het klantenportaal)</span></label>
            <div v-if="mollie_connected" class="mollie-status" :class="{ 'is-removing': form.mollie_disconnect }">
              <template v-if="!form.mollie_disconnect">
                <span class="mollie-status-text">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Mollie gekoppeld — klanten kunnen online betalen
                </span>
                <button type="button" class="btn btn-secondary btn-sm" @click="form.mollie_disconnect = true">Koppeling verwijderen</button>
              </template>
              <template v-else>
                <span class="mollie-status-text">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  De koppeling wordt verwijderd zodra je opslaat — klanten kunnen dan niet meer online betalen.
                </span>
                <button type="button" class="btn btn-secondary btn-sm" @click="form.mollie_disconnect = false">Toch behouden</button>
              </template>
            </div>
            <template v-else>
              <input type="password" v-model="form.mollie_api_key" placeholder="live_... of test_..." autocomplete="off" style="max-width:340px;">
              <div style="font-size:11px;color:var(--text-4);margin-top:4px;line-height:1.6;">
                Plak hier de <b>Live API-sleutel</b> van je eigen Mollie-account (mollie.com → Developers → API-toegangstokens).
                Betalingen gaan rechtstreeks naar jouw rekening; wij zitten er niet tussen.
              </div>
            </template>
            <div v-if="form.errors.mollie_api_key" class="field-error">{{ form.errors.mollie_api_key }}</div>
          </div>
          <div class="form-group" style="margin:0;">
            <label>Standaard voetnoot<span class="label-hint">(onderaan elke factuur)</span></label>
            <textarea v-model="form.invoice_footer" rows="3" placeholder="Bijv. betalingsvoorwaarden, BTW-mededelingen..."></textarea>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header">
          <div>
            <div class="card-title">Dagelijks overzicht</div>
            <div class="card-subtitle">Elke ochtend een mailtje met wat er die dag om aandacht vraagt</div>
          </div>
        </div>
        <div class="card-body">
          <label class="toggle-row">
            <input type="checkbox" v-model="form.daily_notification_enabled">
            <div>
              <div class="toggle-title">Stuur mij elke ochtend een overzicht</div>
              <div class="toggle-sub">
                Vervallen facturen, betalingen van gisteren, wat er binnen een week vervalt en
                concepten die nog klaarstaan. Is er niets te melden, dan krijg je ook geen mail.
              </div>
            </div>
          </label>

          <div class="form-group" style="margin:16px 0 0;" v-if="form.daily_notification_enabled">
            <label>Stuur naar<span class="label-hint">(leeg = je bedrijfs-e-mailadres)</span></label>
            <input type="email" v-model="form.daily_notification_email" maxlength="255" placeholder="jij@bedrijf.nl">
            <div v-if="form.errors.daily_notification_email" class="field-error">{{ form.errors.daily_notification_email }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
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
