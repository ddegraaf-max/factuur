<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { reactive, ref } from 'vue';
import { t } from '@/i18n';

const props = defineProps({
  site: Object,
  slug: String,
  public_url: String,
  ai_enabled: Boolean,
  ai_locked: Boolean,
  allowed: Boolean,
  company: Object,
  leads: Array,
});

const clone = (v) => JSON.parse(JSON.stringify(v));
const form = useForm({ published: !!props.site.published, content: clone(props.site.content) });
const save = () => form.patch(route('settings.site.update'), { preserveScroll: true });

/* ---------- Website maken met AI ---------- */
// De toon gaat als tekst naar de AI; de labels in de keuzelijst worden vertaald, de waarde blijft de brontekst.
const tones = ['vriendelijk en professioneel', 'nuchter en direct', 'warm en persoonlijk', 'zakelijk en formeel'];
const answers = reactive({
  what: props.site.answers?.what || '',
  audience: props.site.answers?.audience || '',
  why: props.site.answers?.why || '',
  tone: props.site.answers?.tone || 'vriendelijk en professioneel',
});
const busy = ref(false);
const aiError = ref('');
const aiNotice = ref('');
const generate = async () => {
  if (busy.value) return;
  busy.value = true; aiError.value = ''; aiNotice.value = '';
  try {
    const { data } = await axios.post(route('settings.site.generate'), answers);
    form.content = clone(data.content);
    aiNotice.value = t('Tekst geschreven — lees hem door, pas aan wat je wilt en klik op Opslaan (en zet hem online).');
  } catch (err) {
    aiError.value = err.response?.data?.message || err.response?.data?.errors?.what?.[0] || t('Genereren is niet gelukt. Probeer het opnieuw.');
  } finally {
    busy.value = false;
  }
};

const addService = () => form.content.services.push({ title: '', description: '' });
const addUsp = () => form.content.usps.push({ title: '', text: '' });

const copied = ref(false);
const copyLink = async () => {
  try { await navigator.clipboard.writeText(props.public_url); copied.value = true; setTimeout(() => (copied.value = false), 2000); } catch { /* geen clipboard */ }
};
</script>

<template>
  <Head :title="$t('Website')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Instellingen') }} / <span class="breadcrumb-current">{{ $t('Website') }}</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="save">{{ form.processing ? $t('Opslaan…') : $t('Opslaan') }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Je eigen website') }}</h1>
        <p class="page-subtitle">{{ $t('Een complete one-pager in je huisstijl: diensten, over ons, waarom jij en een contactformulier waarvan de berichten hier binnenkomen. Laat de tekst schrijven, pas aan en zet online.') }}</p>
      </div>
    </div>

    <div v-if="!allowed" class="card" style="border-color:var(--brand-border);margin-bottom:16px;">
      <div class="card-body" style="font-size:13px;color:var(--text-2);">{{ $t('Je website wordt pas getoond zodra je account actief is (proefperiode of abonnement). Je kunt hem alvast inrichten.') }}</div>
    </div>

    <div class="ws-layout">
      <div class="ws-main">
        <div v-if="ai_enabled" class="card" style="border-color:var(--brand-border);">
          <div class="card-header"><div><div class="card-title">✨ {{ $t('Website maken met AI') }}</div><div class="card-subtitle">{{ $t('Beantwoord vier vragen; de complete tekst wordt voor je geschreven. Daarna pas je alles hieronder aan.') }}</div></div></div>
          <div class="card-body">
            <div class="form-group"><label>{{ $t('Wat doet je bedrijf?') }}</label><input v-model="answers.what" :placeholder="$t('Bijv. schilderwerk binnen en buiten voor particulieren en VvE\'s')" /></div>
            <div class="form-row">
              <div class="form-group"><label>{{ $t('Voor wie?') }}</label><input v-model="answers.audience" :placeholder="$t('Bijv. huiseigenaren in regio Eindhoven')" /></div>
              <div class="form-group"><label>{{ $t('Toon') }}</label><select v-model="answers.tone"><option v-for="tone in tones" :key="tone" :value="tone">{{ $t(tone) }}</option></select></div>
            </div>
            <div class="form-group"><label>{{ $t('Waarom kiezen klanten voor jou?') }}</label><input v-model="answers.why" :placeholder="$t('Bijv. 20 jaar ervaring, altijd binnen een week een afspraak, vaste prijs vooraf')" /></div>
            <button class="btn btn-primary btn-sm" :disabled="busy || answers.what.trim().length < 3" @click="generate">{{ busy ? $t('Schrijven… (± 30 seconden)') : (form.content.hero.title ? $t('Opnieuw laten schrijven') : $t('Schrijf mijn website')) }}</button>
            <div v-if="aiNotice" class="ai-ok">{{ aiNotice }}</div>
            <div v-if="aiError" class="field-error" style="margin-top:8px;">{{ aiError }}</div>
          </div>
        </div>
        <div v-else-if="ai_locked" class="card"><div class="card-body" style="font-size:13px;color:var(--text-2);line-height:1.6;">✨ <b>{{ $t('Website maken met AI') }}</b> — {{ $t('beantwoord vier vragen en de complete tekst wordt geschreven. Onderdeel van het') }} <b>Slim</b>{{ $t('-abonnement.') }} <Link :href="route('billing.show')" style="color:var(--brand);font-weight:600;">{{ $t('Bekijk de abonnementen') }}</Link>. {{ $t('Zelf schrijven kan altijd, hieronder.') }}</div></div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Bovenaan (hero)') }}</div></div>
          <div class="card-body">
            <div class="form-group"><label>{{ $t('Kop') }}</label><input v-model="form.content.hero.title" maxlength="90" :placeholder="$t('Bijv. Schilderwerk waar je jaren plezier van hebt')" /><div v-if="form.errors.content" class="field-error">{{ form.errors.content }}</div></div>
            <div class="form-group"><label>{{ $t('Onder de kop') }}</label><textarea v-model="form.content.hero.subtitle" rows="2" maxlength="240"></textarea></div>
            <div class="form-group"><label>{{ $t('Knoptekst') }}</label><input v-model="form.content.hero.cta" maxlength="40" /></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div><div class="card-title">{{ $t('Diensten') }}</div><div class="card-subtitle">{{ $t('Vier tot zes werkt het best.') }}</div></div><button class="btn btn-secondary btn-sm" @click="addService">+ {{ $t('Dienst') }}</button></div>
          <div class="card-body">
            <div v-for="(s, i) in form.content.services" :key="'s' + i" class="item">
              <input v-model="s.title" maxlength="60" :placeholder="$t('Naam van de dienst')" />
              <textarea v-model="s.description" rows="2" maxlength="320" :placeholder="$t('Twee zinnen over deze dienst')"></textarea>
              <button class="btn btn-secondary btn-sm btn-danger-ghost" @click="form.content.services.splice(i, 1)">{{ $t('Verwijderen') }}</button>
            </div>
            <div v-if="!form.content.services.length" class="muted">{{ $t('Nog geen diensten — laat ze schrijven of voeg er zelf een toe.') }}</div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div><div class="card-title">{{ $t('Waarom jij') }}</div><div class="card-subtitle">{{ $t('Drie of vier korte redenen.') }}</div></div><button class="btn btn-secondary btn-sm" @click="addUsp">+ {{ $t('Reden') }}</button></div>
          <div class="card-body">
            <div v-for="(u, i) in form.content.usps" :key="'u' + i" class="item">
              <input v-model="u.title" maxlength="60" :placeholder="$t('Bijv. Vaste prijs vooraf')" />
              <input v-model="u.text" maxlength="220" :placeholder="$t('Eén zin toelichting')" />
              <button class="btn btn-secondary btn-sm btn-danger-ghost" @click="form.content.usps.splice(i, 1)">{{ $t('Verwijderen') }}</button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Over ons') }}</div></div>
          <div class="card-body">
            <div class="form-group"><label>{{ $t('Titel') }}</label><input v-model="form.content.about.title" maxlength="80" /></div>
            <div class="form-group"><label>{{ $t('Tekst') }}</label><textarea v-model="form.content.about.text" rows="5" maxlength="1200"></textarea></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Contact en vindbaarheid') }}</div></div>
          <div class="card-body">
            <div class="form-group"><label>{{ $t('Titel boven het contactformulier') }}</label><input v-model="form.content.contact.title" maxlength="80" /></div>
            <div class="form-group"><label>{{ $t('Uitnodigende zin') }}</label><input v-model="form.content.contact.text" maxlength="320" /></div>
            <div class="form-row">
              <div class="form-group"><label>{{ $t('Paginatitel voor Google') }}</label><input v-model="form.content.seo.title" maxlength="70" /></div>
              <div class="form-group"><label>{{ $t('Omschrijving voor Google') }}</label><input v-model="form.content.seo.description" maxlength="160" /></div>
            </div>
            <p class="muted">{{ $t('Telefoon, e-mail en adres komen uit') }} <Link :href="route('settings.company')" style="color:var(--brand);font-weight:600;">{{ $t('Bedrijfsgegevens') }}</Link>; {{ $t('logo en kleuren uit') }} <Link :href="route('settings.brand')" style="color:var(--brand);font-weight:600;">{{ $t('Huisstijl') }}</Link>.</p>
          </div>
        </div>
      </div>

      <div class="ws-side">
        <div class="card">
          <div class="card-header"><div class="card-title">{{ $t('Online zetten') }}</div></div>
          <div class="card-body">
            <div class="form-group"><label>{{ $t('Adres') }}</label><div class="url">{{ public_url }}</div>
              <div class="link-actions"><a :href="public_url" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">{{ $t('Bekijk') }}</a><button class="btn btn-secondary btn-sm" @click="copyLink">{{ copied ? $t('Gekopieerd!') : $t('Kopieer link') }}</button></div>
              <p class="muted" style="margin-top:8px;">{{ $t('Het adres verander je bij') }} <Link :href="route('settings.card')" style="color:var(--brand);font-weight:600;">{{ $t('Visitekaartje') }}</Link> {{ $t('(zelfde link-naam).') }}</p>
            </div>
            <label class="toggle-row"><input type="checkbox" v-model="form.published"><div><div class="toggle-title">{{ $t('Website online') }}</div><div class="toggle-sub">{{ $t('Uit = de link geeft "niet gevonden".') }}</div></div></label>
            <button class="btn btn-primary btn-block" style="margin-top:12px;" :disabled="form.processing" @click="save">{{ form.processing ? $t('Opslaan…') : $t('Opslaan') }}</button>
            <p v-if="site.generated_at" class="muted" style="margin-top:8px;">{{ $t('Laatst door AI geschreven: :date', { date: site.generated_at }) }}</p>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div><div class="card-title">{{ $t('Berichten via je website') }}</div><div class="card-subtitle">{{ $t('Ook per e-mail naar :email.', { email: company.email || $t('je bedrijfsmail') }) }}</div></div></div>
          <div class="card-body">
            <div v-if="!leads.length" class="muted">{{ $t('Nog geen berichten.') }}</div>
            <div v-for="l in leads" :key="l.id" class="lead">
              <div class="lead-head"><b>{{ l.name }}</b><span>{{ l.received_label }}</span></div>
              <div class="lead-meta"><a :href="'mailto:' + l.email" style="color:var(--brand);">{{ l.email }}</a><span v-if="l.phone"> · {{ l.phone }}</span></div>
              <div class="lead-msg">{{ l.message }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.ws-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 18px; align-items: start; }
@media (max-width: 1000px) { .ws-layout { grid-template-columns: 1fr; } }
.card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.item { display: grid; gap: 8px; padding: 12px; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 10px; background: var(--surface); }
.item input, .item textarea, .form-group textarea { width: 100%; font: inherit; padding: 9px 10px; border: 1px solid var(--border-strong); border-radius: 8px; }
.item .btn { justify-self: end; }
.muted { font-size: 13px; color: var(--text-2); line-height: 1.5; }
.url { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; word-break: break-all; padding: 8px 10px; background: var(--surface-2); border-radius: 8px; }
.link-actions { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.ai-ok { margin-top: 10px; padding: 10px 12px; border-radius: 8px; background: #e8f7ee; color: #157347; font-size: 13px; }
.lead { padding: 10px 0; border-top: 1px solid var(--border); font-size: 13px; }
.lead:first-of-type { border-top: 0; }
.lead-head { display: flex; justify-content: space-between; gap: 10px; color: var(--text-2); }
.lead-head b { color: var(--text); }
.lead-meta { color: var(--text-2); margin: 2px 0 6px; }
.lead-msg { white-space: pre-line; color: var(--text); }
</style>
