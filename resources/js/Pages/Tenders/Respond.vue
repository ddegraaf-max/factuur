<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * Reactieformulier voor de onderaannemer (openbaar, via de tokenlink). Geen
 * inlog, geen app-layout: één nette kaart met de aanvraag en het formulier.
 */
const props = defineProps({
  valid: Boolean,
  token: String,
  company: Object,
  round: Object,
  request: Object,
});

const page = usePage();
const brand = computed(() => page.props.brand || {});
const flash = computed(() => page.props.flash?.flash || null);
const pageError = computed(() => (page.props.errors || {}).tender ?? null);
const color = computed(() => props.company?.color || brand.value.color || '#DC2626');

const form = useForm({
  price: props.request?.price ?? '',
  available_week: props.request?.available_week || '',
  valid_until: props.request?.valid_until || '',
  remarks: props.request?.remarks || '',
  attachment: null,
});
const declineForm = useForm({ reason: '' });
const showDecline = ref(false);

const submit = () => form
  .transform((d) => ({ ...d, price: String(d.price).replace(',', '.') }))
  .post(route('tender.respond', props.token), { forceFormData: true, preserveScroll: true, onSuccess: () => { form.attachment = null; } });
const decline = () => declineForm.post(route('tender.decline', props.token), { preserveScroll: true, onSuccess: () => { showDecline.value = false; } });

const answered = computed(() => ['responded', 'awarded', 'rejected'].includes(props.request?.status));
const final = computed(() => !props.round?.open || ['awarded', 'rejected'].includes(props.request?.status));
</script>

<template>
  <Head :title="valid ? $t('Prijsaanvraag :title', { title: round.title }) : $t('Prijsaanvraag')" />
  <div class="tr-shell">
    <div class="tr-card">
      <div class="tr-head" :style="{ background: color }">
        <div class="tr-company">{{ company?.name || brand.name }}</div>
        <div class="tr-kind">{{ $t('Prijsaanvraag') }}</div>
      </div>

      <div v-if="!valid" class="tr-body">
        <h1>{{ $t('Deze link is niet (meer) geldig') }}</h1>
        <p>{{ $t('Neem contact op met de aanvrager als u alsnog een prijs wilt doorgeven.') }}</p>
      </div>

      <div v-else class="tr-body">
        <h1>{{ round.title }}</h1>
        <p class="tr-intro">{{ $t(':company vraagt u om een prijs en uw beschikbaarheid voor dit onderdeel. Invullen duurt een minuut.', { company: company.name }) }}</p>

        <div class="tr-box">
          <div v-if="round.location"><span class="k">{{ $t('Locatie') }}</span><span>{{ round.location }}</span></div>
          <div v-if="round.start_week"><span class="k">{{ $t('Gewenste start') }}</span><span>{{ $t('week :week', { week: round.start_week }) }}</span></div>
          <div><span class="k">{{ $t('Reageren vóór') }}</span><span><strong>{{ round.deadline_label }}</strong></span></div>
          <div v-if="round.description" class="tr-desc">{{ round.description }}</div>
        </div>

        <div v-if="flash" class="tr-flash">{{ flash }}</div>
        <div v-if="pageError" class="tr-error">{{ pageError }}</div>

        <template v-if="request.status === 'awarded'">
          <div class="tr-final good"><strong>{{ $t('Gefeliciteerd: de opdracht is aan u gegund.') }}</strong> {{ $t(':company neemt contact met u op over de planning.', { company: company.name }) }}</div>
        </template>
        <template v-else-if="request.status === 'rejected'">
          <div class="tr-final">{{ $t('Deze opdracht is aan een ander bedrijf gegund. Bedankt voor uw prijsopgave.') }}</div>
        </template>
        <template v-else-if="!round.open">
          <div class="tr-final">{{ $t('Deze prijsaanvraag is gesloten.') }}</div>
        </template>
        <template v-else-if="request.status === 'declined' && !showDecline">
          <div class="tr-final">{{ $t('U heeft aangegeven deze opdracht niet te kunnen doen. Toch een prijs doorgeven? Dat kan hieronder.') }}</div>
        </template>

        <form v-if="!final" @submit.prevent="submit" class="tr-form">
          <div v-if="answered" class="tr-note">{{ $t('Uw prijs is ontvangen op :date. U kunt hem hieronder nog aanpassen zolang de aanvraag open is.', { date: request.responded_at_label }) }}</div>
          <div class="tr-row">
            <div class="tr-field">
              <label>{{ $t('Prijs excl. btw') }} *</label>
              <div class="tr-money"><span>€</span><input type="text" inputmode="decimal" v-model="form.price" placeholder="0,00" required></div>
              <div v-if="form.errors.price" class="tr-err">{{ form.errors.price }}</div>
            </div>
            <div class="tr-field">
              <label>{{ $t('Beschikbaar vanaf week') }}</label>
              <input type="text" v-model="form.available_week" maxlength="12" :placeholder="round.start_week || '2026-W42'">
            </div>
          </div>
          <div class="tr-row">
            <div class="tr-field">
              <label>{{ $t('Prijs geldig tot') }}</label>
              <input type="date" v-model="form.valid_until">
            </div>
            <div class="tr-field">
              <label>{{ $t('Eigen offerte (PDF)') }} <span class="tr-hint">{{ $t('optioneel') }}</span></label>
              <input type="file" accept="application/pdf,image/png,image/jpeg" @change="form.attachment = $event.target.files[0] || null">
              <div v-if="request.attachment_name && !form.attachment" class="tr-hint">{{ $t('Eerder toegevoegd: :name', { name: request.attachment_name }) }}</div>
              <div v-if="form.errors.attachment" class="tr-err">{{ form.errors.attachment }}</div>
            </div>
          </div>
          <div class="tr-field">
            <label>{{ $t('Opmerkingen') }} <span class="tr-hint">{{ $t('(wat zit er wel/niet in, voorwaarden)') }}</span></label>
            <textarea v-model="form.remarks" rows="4" maxlength="3000"></textarea>
          </div>
          <div class="tr-actions">
            <button type="submit" class="tr-btn" :style="{ background: color }" :disabled="form.processing">{{ answered ? $t('Prijs bijwerken') : $t('Prijs doorgeven') }}</button>
            <button v-if="request.status !== 'declined'" type="button" class="tr-link" @click="showDecline = !showDecline">{{ $t('Ik kan deze opdracht niet doen') }}</button>
          </div>
        </form>

        <form v-if="!final && showDecline" @submit.prevent="decline" class="tr-decline">
          <label>{{ $t('Reden (optioneel)') }}</label>
          <textarea v-model="declineForm.reason" rows="2" maxlength="1000" :placeholder="$t('Bijv. geen capaciteit in die periode')"></textarea>
          <button type="submit" class="tr-btn secondary" :disabled="declineForm.processing">{{ $t('Afzeggen') }}</button>
        </form>

        <div class="tr-contact">
          {{ $t('Vragen? Neem contact op met :company', { company: company.name }) }}<template v-if="company.phone"> · {{ company.phone }}</template><template v-if="company.email"> · <a :href="'mailto:' + company.email">{{ company.email }}</a></template>
        </div>
      </div>
    </div>
    <div class="tr-footer">{{ $t('Verzonden via :brand namens :company.', { brand: brand.name, company: company?.name || '' }) }}</div>
  </div>
</template>

<style scoped>
.tr-shell { min-height: 100vh; background: #F5F5F4; padding: 32px 16px; font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1C1917; }
.tr-card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(28,25,23,0.08); overflow: hidden; }
.tr-head { padding: 22px 32px; color: #fff; display: flex; justify-content: space-between; align-items: baseline; }
.tr-company { font-size: 18px; font-weight: 700; }
.tr-kind { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.85; }
.tr-body { padding: 28px 32px 30px; }
h1 { font-size: 22px; margin: 0 0 8px; letter-spacing: -0.015em; }
.tr-intro { color: #44403C; line-height: 1.6; margin: 0 0 16px; font-size: 14.5px; }
.tr-box { background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 10px; padding: 14px 16px; font-size: 14px; margin-bottom: 18px; }
.tr-box > div { display: flex; gap: 12px; padding: 3px 0; }
.tr-box .k { color: #78716C; width: 140px; flex: 0 0 140px; }
.tr-desc { display: block !important; white-space: pre-wrap; margin-top: 10px; padding-top: 10px; border-top: 1px solid #E7E5E4; line-height: 1.6; color: #44403C; }
.tr-flash { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.tr-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 14px; }
.tr-final { background: #F5F5F4; border-radius: 8px; padding: 12px 14px; font-size: 14.5px; line-height: 1.6; margin-bottom: 14px; }
.tr-final.good { background: #ECFDF5; color: #065F46; }
.tr-note { font-size: 13px; color: #78716C; margin-bottom: 12px; }
.tr-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 560px) { .tr-row { grid-template-columns: 1fr; } }
.tr-field { margin-bottom: 14px; }
.tr-field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
.tr-hint { font-weight: 400; color: #78716C; font-size: 12px; }
.tr-field input, .tr-field textarea { width: 100%; box-sizing: border-box; border: 1px solid #D6D3D1; border-radius: 8px; padding: 9px 12px; font-size: 14px; font-family: inherit; }
.tr-money { display: flex; align-items: center; border: 1px solid #D6D3D1; border-radius: 8px; padding-left: 10px; }
.tr-money span { color: #78716C; }
.tr-money input { border: 0; }
.tr-err { color: #B91C1C; font-size: 12px; margin-top: 4px; }
.tr-actions { display: flex; align-items: center; gap: 16px; margin-top: 6px; flex-wrap: wrap; }
.tr-btn { border: 0; color: #fff; font-weight: 600; font-size: 15px; padding: 12px 22px; border-radius: 8px; cursor: pointer; }
.tr-btn.secondary { background: #78716C; font-size: 14px; padding: 9px 16px; }
.tr-btn:disabled { opacity: 0.6; cursor: default; }
.tr-link { background: none; border: 0; color: #78716C; text-decoration: underline; cursor: pointer; font-size: 13.5px; }
.tr-decline { margin-top: 16px; padding-top: 16px; border-top: 1px solid #E7E5E4; }
.tr-decline label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
.tr-decline textarea { width: 100%; box-sizing: border-box; border: 1px solid #D6D3D1; border-radius: 8px; padding: 9px 12px; font-size: 14px; font-family: inherit; margin-bottom: 10px; }
.tr-contact { margin-top: 22px; padding-top: 16px; border-top: 1px solid #E7E5E4; font-size: 13px; color: #78716C; }
.tr-contact a { color: inherit; }
.tr-footer { text-align: center; font-size: 12px; color: #A8A29E; margin-top: 18px; }
</style>
