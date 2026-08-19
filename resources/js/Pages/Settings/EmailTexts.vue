<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  texts: Object,    // { invoice_subject, invoice_body, quote_subject, quote_body } — '' = standaard
  defaults: Object, // standaardteksten (NL), als placeholder/voorbeeld
});

const form = useForm({ ...props.texts });

const submit = () => form.patch(route('settings.emails.update'), { preserveScroll: true });

const reset = (fields) => fields.forEach((f) => { form[f] = ''; });
</script>

<template>
  <Head title="E-mailteksten" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">E-mailteksten</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" @click="submit" :disabled="form.processing">Opslaan</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">E-mailteksten</h1>
        <p class="page-subtitle">Bepaal zelf het onderwerp en de tekst van je factuur- en offertemail. Leeg laten = de standaardtekst.</p>
      </div>
    </div>

    <div class="single-col">
      <div class="card">
        <div class="card-header"><div class="card-title">Factuurmail</div></div>
        <div class="card-body" style="padding:18px 20px;">
          <p class="txt-help">
            Gebruik variabelen — ze worden automatisch ingevuld:
            <code>{klant}</code> <code>{bedrijf}</code> <code>{factuurnummer}</code>
            <code>{factuurdatum}</code> <code>{vervaldatum}</code> <code>{bedrag}</code>
            <code>{openstaand}</code> <code>{iban}</code>.
            Begin je bericht zelf met een aanhef, bijvoorbeeld <code>Beste {klant},</code> —
            de standaard-aanhef vervalt bij een eigen tekst.
          </p>
          <div class="txt-block">
            <div class="txt-label">Onderwerp</div>
            <input type="text" v-model="form.invoice_subject" maxlength="200" :placeholder="defaults.invoice_subject" />
            <div class="txt-label">Bericht</div>
            <textarea v-model="form.invoice_body" rows="8" maxlength="4000" :placeholder="defaults.invoice_body"></textarea>
          </div>
          <div class="txt-note">
            De knop naar het klantenportaal, verrekeningsmeldingen ("reeds doorgestort") en de PDF-bijlage
            blijven automatisch onder je tekst staan.
            <button v-if="form.invoice_subject || form.invoice_body" type="button" class="txt-reset" @click="reset(['invoice_subject', 'invoice_body'])">Terug naar standaard</button>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:14px;">
        <div class="card-header"><div class="card-title">Offertemail</div></div>
        <div class="card-body" style="padding:18px 20px;">
          <p class="txt-help">
            Variabelen: <code>{klant}</code> <code>{bedrijf}</code> <code>{offertenummer}</code>
            <code>{offertedatum}</code> <code>{geldigtot}</code> <code>{bedrag}</code>.
            De aanhef ("Beste {klant},") staat er als kop automatisch boven.
            Vul je bij een specifieke offerte een eigen introtekst in, dan gaat díe voor.
          </p>
          <div class="txt-block">
            <div class="txt-label">Onderwerp</div>
            <input type="text" v-model="form.quote_subject" maxlength="200" :placeholder="defaults.quote_subject" />
            <div class="txt-label">Bericht</div>
            <textarea v-model="form.quote_body" rows="5" maxlength="4000" :placeholder="defaults.quote_body"></textarea>
          </div>
          <div class="txt-note">
            Het totaalbedrag, de geldigheid en de knop "Bekijk en onderteken online" blijven automatisch staan.
            <button v-if="form.quote_subject || form.quote_body" type="button" class="txt-reset" @click="reset(['quote_subject', 'quote_body'])">Terug naar standaard</button>
          </div>
        </div>
      </div>

      <div class="txt-lang-note">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
          <strong>Let op bij Engelstalige klanten:</strong> een eigen tekst wordt letterlijk gebruikt voor álle klanten,
          ook klanten met taalinstelling Engels. De standaardteksten volgen wél automatisch de taal van de klant.
          Herinneringen en aanmaningen hebben hun eigen teksten onder Instellingen → Herinneringen.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.txt-help { font-size: 12.5px; color: var(--text-3); line-height: 1.8; margin: 0 0 18px; }
.txt-help code { background: var(--surface-2); border: 1px solid var(--border); border-radius: 4px; padding: 1px 6px; font-size: 11.5px; color: var(--text-2); }
.txt-block { margin-bottom: 14px; }
.txt-label { font-size: 12.5px; font-weight: 600; color: var(--text-2); margin: 12px 0 6px; }
.txt-block input, .txt-block textarea { width: 100%; }
.txt-note { font-size: 12.5px; color: var(--text-3); line-height: 1.7; }
.txt-reset { background: none; border: none; padding: 0; margin-left: 6px; font-size: 12.5px; color: var(--brand); cursor: pointer; text-decoration: underline; }
.txt-lang-note {
  display: flex; gap: 12px; align-items: flex-start;
  background: #FEF9EC; border: 1px solid #FDE68A; border-radius: 12px;
  padding: 14px 18px; margin-top: 14px; font-size: 13px; line-height: 1.65; color: #713F12;
}
.txt-lang-note svg { flex: none; margin-top: 2px; color: #B45309; }
</style>
