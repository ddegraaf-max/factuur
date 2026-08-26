<script setup>
import { computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  texts: Object,    // { invoice_*, quote_*, thanks_* } — '' = standaard
  defaults: Object, // standaardteksten (NL), als placeholder/voorbeeld
  thanks_enabled: Boolean,
  review_url: { type: String, default: '' },
  accept_enabled: Boolean,
});

const form = useForm({
  ...props.texts,
  thanks_enabled: !!props.thanks_enabled,
  review_url: props.review_url || '',
  accept_enabled: !!props.accept_enabled,
});

const previewAcceptUrl = computed(() => route('settings.emails.preview.accept', {
  accept_subject: form.accept_subject || '',
  accept_body: form.accept_body || '',
}));

const submit = () => form.patch(route('settings.emails.update'), { preserveScroll: true });

const reset = (fields) => fields.forEach((f) => { form[f] = ''; });

// Voorbeeld van de bedankmail met de tekst zoals die nú in het formulier staat
// (nog niet opgeslagen) — opent in een nieuw tabblad.
const previewUrl = computed(() => route('settings.emails.preview.thanks', {
  thanks_subject: form.thanks_subject || '',
  thanks_body: form.thanks_body || '',
  review_url: form.review_url || '',
}));
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
        <p class="page-subtitle">Bepaal zelf het onderwerp en de tekst van je factuur-, bedank- en offertemail. Leeg laten = de standaardtekst.</p>
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

      <!-- Bedankmail na betaling -->
      <div class="card" style="margin-top:14px;">
        <div class="card-header thanks-head">
          <div>
            <div class="card-title">Bedankmail na betaling</div>
            <div class="thanks-sub">Een vriendelijk bedankje zodra een factuur volledig is betaald — in jouw huisstijl.</div>
          </div>
          <label class="switch" :class="{ on: form.thanks_enabled }">
            <input type="checkbox" v-model="form.thanks_enabled" />
            <span class="switch-track"><span class="switch-thumb"></span></span>
            <span class="switch-text">{{ form.thanks_enabled ? 'Aan' : 'Uit' }}</span>
          </label>
        </div>
        <div class="card-body" style="padding:18px 20px;">
          <div class="thanks-flow" :class="{ muted: !form.thanks_enabled }">
            <div class="flow-step">
              <span class="flow-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/></svg></span>
              <div><b>Bankkoppeling</b><span>Automatisch zodra je een ontvangst koppelt.</span></div>
            </div>
            <div class="flow-step">
              <span class="flow-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span>
              <div><b>iDEAL via Mollie</b><span>Direct na de online betaling.</span></div>
            </div>
            <div class="flow-step">
              <span class="flow-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
              <div><b>Handmatig geboekt</b><span>Jij kiest het per betaling met een vinkje.</span></div>
            </div>
          </div>
          <p class="txt-help">
            De mail bevat automatisch een overzicht van de betaling (factuur, bedrag, datum, betaalwijze), een knop naar het
            klantenportaal en de factuur met het stempel <b>BETAALD</b> als PDF — een betaalbewijs voor de administratie van je klant.
            Variabelen: <code>{klant}</code> <code>{bedrijf}</code> <code>{factuurnummer}</code> <code>{factuurdatum}</code>
            <code>{bedrag}</code> <code>{betaaldatum}</code> <code>{betaalwijze}</code>.
            Begin je bericht zelf met een aanhef — de standaard-aanhef vervalt bij een eigen tekst.
          </p>
          <div class="txt-block">
            <div class="txt-label">Onderwerp</div>
            <input type="text" v-model="form.thanks_subject" maxlength="200" :placeholder="defaults.thanks_subject" />
            <div class="txt-label">Bericht</div>
            <textarea v-model="form.thanks_body" rows="5" maxlength="4000" :placeholder="defaults.thanks_body"></textarea>
            <div class="txt-label">Reviewlink <span class="txt-opt">optioneel</span></div>
            <input type="text" v-model="form.review_url" maxlength="500" placeholder="https://g.page/r/... of je Trustpilot-, Klantenvertellen- of Google-pagina" />
            <div v-if="form.errors.review_url" class="field-error">{{ form.errors.review_url }}</div>
            <div class="txt-hint">Met een link krijgt de bedankmail een knop "Laat een review achter". Direct na een betaling is hét moment om erom te vragen.</div>
          </div>
          <div class="txt-note">
            <a :href="previewUrl" target="_blank" rel="noopener" class="txt-preview">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              Bekijk voorbeeld
            </a>
            <span class="txt-sep">·</span>
            <span>Overzicht, portaalknop en PDF-bijlage blijven automatisch staan.</span>
            <button v-if="form.thanks_subject || form.thanks_body" type="button" class="txt-reset" @click="reset(['thanks_subject', 'thanks_body'])">Terug naar standaard</button>
          </div>
        </div>
      </div>

      <!-- Bevestiging na akkoord op een offerte -->
      <div class="card" style="margin-top:14px;">
        <div class="card-header thanks-head">
          <div>
            <div class="card-title">Bevestiging na akkoord op offerte</div>
            <div class="thanks-sub">Naar de klant zodra hij de offerte in het portaal ondertekent — met de ondertekende offerte als PDF.</div>
          </div>
          <label class="switch" :class="{ on: form.accept_enabled }">
            <input type="checkbox" v-model="form.accept_enabled" />
            <span class="switch-track"><span class="switch-thumb"></span></span>
            <span class="switch-text">{{ form.accept_enabled ? 'Aan' : 'Uit' }}</span>
          </label>
        </div>
        <div class="card-body" style="padding:18px 20px;">
          <p class="txt-help">
            De mail bevat automatisch het overzicht (offertenummer, datum akkoord, ondertekenaar, totaal), het termijnplan als dat er is,
            een knop naar het portaal en de offerte als PDF. Markeer je een offerte zelf als geaccepteerd (bijv. akkoord per telefoon),
            dan kies je per offerte of de bevestiging meegaat. Hier stel je de "hoe nu verder"-tekst in.
            Variabelen: <code>{klant}</code> <code>{ondertekenaar}</code> <code>{bedrijf}</code> <code>{offertenummer}</code>
            <code>{offertedatum}</code> <code>{akkoorddatum}</code> <code>{bedrag}</code>.
            Begin je bericht zelf met een aanhef — de standaard-aanhef vervalt bij een eigen tekst.
          </p>
          <div class="txt-block">
            <div class="txt-label">Onderwerp</div>
            <input type="text" v-model="form.accept_subject" maxlength="200" :placeholder="defaults.accept_subject" />
            <div class="txt-label">Bericht</div>
            <textarea v-model="form.accept_body" rows="6" maxlength="4000" :placeholder="defaults.accept_body"></textarea>
          </div>
          <div class="txt-note">
            <a :href="previewAcceptUrl" target="_blank" rel="noopener" class="txt-preview">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              Bekijk voorbeeld
            </a>
            <span class="txt-sep">·</span>
            <span>Overzicht, termijnplan, portaalknop en PDF-bijlage blijven automatisch staan.</span>
            <button v-if="form.accept_subject || form.accept_body" type="button" class="txt-reset" @click="reset(['accept_subject', 'accept_body'])">Terug naar standaard</button>
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
          ook klanten met taalinstelling Engels. De standaardteksten (ook die van de bedankmail) volgen wél automatisch de taal van de klant.
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
.txt-opt { font-weight: 400; color: var(--text-4); margin-left: 4px; }
.txt-block input, .txt-block textarea { width: 100%; }
.txt-hint { font-size: 12px; color: var(--text-3); line-height: 1.6; margin-top: 6px; }
.txt-note { font-size: 12.5px; color: var(--text-3); line-height: 1.7; display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
.txt-sep { color: var(--text-4); }
.txt-reset { background: none; border: none; padding: 0; margin-left: 6px; font-size: 12.5px; color: var(--brand); cursor: pointer; text-decoration: underline; }
.txt-preview { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--brand); text-decoration: none; }
.txt-preview:hover { text-decoration: underline; }
.txt-preview svg { width: 15px; height: 15px; }

/* Kop met aan/uit-schakelaar */
.thanks-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.thanks-sub { font-size: 12.5px; color: var(--text-3); margin-top: 3px; }
.switch { position: relative; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; flex: none; }
.switch input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; margin: 0; }
.switch-track { position: relative; width: 40px; height: 22px; border-radius: 999px; background: var(--border); transition: background .15s; flex: none; }
.switch-thumb { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.25); transition: transform .15s; }
.switch.on .switch-track { background: var(--success); }
.switch.on .switch-thumb { transform: translateX(18px); }
.switch input:focus-visible + .switch-track { outline: 2px solid var(--brand); outline-offset: 2px; }
.switch-text { font-size: 13px; font-weight: 600; color: var(--text-2); min-width: 26px; }

/* De drie routes waarlangs een betaling binnenkomt */
.thanks-flow { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 0 0 16px; transition: opacity .15s; }
.thanks-flow.muted { opacity: .55; }
.flow-step { display: flex; gap: 10px; align-items: flex-start; border: 1px solid var(--border); border-radius: 10px; padding: 11px 12px; background: var(--surface-2); }
.flow-step b { display: block; font-size: 12.5px; color: var(--text-1); }
.flow-step span:not(.flow-ico) { display: block; font-size: 12px; color: var(--text-3); line-height: 1.5; margin-top: 2px; }
.flow-ico { width: 30px; height: 30px; border-radius: 8px; background: #fff; border: 1px solid var(--border); color: var(--brand); display: inline-flex; align-items: center; justify-content: center; flex: none; }
.flow-ico svg { width: 15px; height: 15px; }
@media (max-width: 720px) { .thanks-flow { grid-template-columns: 1fr; } }

.txt-lang-note {
  display: flex; gap: 12px; align-items: flex-start;
  background: #FEF9EC; border: 1px solid #FDE68A; border-radius: 12px;
  padding: 14px 18px; margin-top: 14px; font-size: 13px; line-height: 1.65; color: #713F12;
}
.txt-lang-note svg { flex: none; margin-top: 2px; color: #B45309; }
</style>
