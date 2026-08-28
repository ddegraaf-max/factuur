<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import QRCode from 'qrcode';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  card: Object,
  slug: String,
  public_url: String,
  site_published: Boolean,
  allowed: Boolean,
  company: Object,
});

const form = useForm({
  published: !!props.card.published,
  contact_name: props.card.contact_name || '',
  job_title: props.card.job_title || '',
  tagline: props.card.tagline || '',
  whatsapp: props.card.whatsapp || '',
  linkedin_url: props.card.linkedin_url || '',
  show_kvk: props.card.show_kvk !== false,
  show_vat: !!props.card.show_vat,
  show_address: props.card.show_address !== false,
  public_slug: props.slug,
});

const save = () => form.patch(route('settings.card.update'), { preserveScroll: true });

const base = computed(() => props.public_url.replace(/\/[^/]*$/, '/'));
const liveUrl = computed(() => base.value + (form.public_slug || props.slug));

/* QR-code in de merkkleur, als PNG te downloaden voor drukwerk of e-mailhandtekening. */
const qr = ref('');
const renderQr = async () => {
  try {
    qr.value = await QRCode.toDataURL(liveUrl.value, { width: 640, margin: 1, color: { dark: props.company.brand_color || '#1C1917', light: '#FFFFFF' } });
  } catch { qr.value = ''; }
};
watch(liveUrl, renderQr, { immediate: true });

const copied = ref(false);
const copyLink = async () => {
  try { await navigator.clipboard.writeText(liveUrl.value); copied.value = true; setTimeout(() => (copied.value = false), 2000); } catch { /* geen clipboard */ }
};

const initial = computed(() => (props.company.name || 'B').trim().charAt(0).toUpperCase());
</script>

<template>
  <Head title="Digitaal visitekaartje" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">Visitekaartje</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" :disabled="form.processing" @click="save">{{ form.processing ? 'Opslaan…' : 'Opslaan' }}</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Digitaal visitekaartje</h1>
        <p class="page-subtitle">Eén link (of QR-code) met al je contactgegevens in je eigen huisstijl — voor op je kaartje, in je e-mailhandtekening of op social media.</p>
      </div>
    </div>

    <div v-if="!allowed" class="card" style="border-color:var(--brand-border);margin-bottom:16px;">
      <div class="card-body" style="font-size:13px;color:var(--text-2);">Je visitekaartje wordt pas getoond zodra je account actief is (proefperiode of abonnement). Je kunt hem alvast inrichten.</div>
    </div>

    <div class="vk-layout">
      <div class="vk-settings">
        <div class="card">
          <div class="card-header"><div><div class="card-title">Adres van je kaartje</div><div class="card-subtitle">Kort en herkenbaar — dit staat ook in de QR-code.</div></div></div>
          <div class="card-body">
            <div class="form-group">
              <label>Link</label>
              <div class="slug-row"><span class="slug-prefix">{{ base }}</span><input v-model="form.public_slug" class="slug-input" placeholder="jouw-bedrijf" /></div>
              <div v-if="form.errors.public_slug" class="field-error">{{ form.errors.public_slug }}</div>
              <div class="link-actions">
                <a :href="liveUrl" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Bekijk je kaartje</a>
                <button class="btn btn-secondary btn-sm" @click="copyLink">{{ copied ? 'Gekopieerd!' : 'Kopieer link' }}</button>
              </div>
            </div>
            <label class="toggle-row">
              <input type="checkbox" v-model="form.published">
              <div><div class="toggle-title">Kaartje online zetten</div><div class="toggle-sub">Uit = de link geeft "niet gevonden". Handig als je nog aan het inrichten bent.</div></div>
            </label>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">Wie staat er op</div></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group"><label>Naam</label><input v-model="form.contact_name" placeholder="Bijv. Daniël de Graaf" /><div v-if="form.errors.contact_name" class="field-error">{{ form.errors.contact_name }}</div></div>
              <div class="form-group"><label>Functie</label><input v-model="form.job_title" placeholder="Bijv. Eigenaar" /></div>
            </div>
            <div class="form-group"><label>Eén zin over je bedrijf</label><input v-model="form.tagline" maxlength="160" placeholder="Bijv. Installatiewerk voor woning en bedrijf — snel en netjes" /></div>
            <div class="form-row">
              <div class="form-group"><label>WhatsApp-nummer</label><input v-model="form.whatsapp" placeholder="06 12345678" /><div v-if="form.errors.whatsapp" class="field-error">{{ form.errors.whatsapp }}</div></div>
              <div class="form-group"><label>LinkedIn-profiel</label><input v-model="form.linkedin_url" placeholder="https://linkedin.com/in/…" /><div v-if="form.errors.linkedin_url" class="field-error">{{ form.errors.linkedin_url }}</div></div>
            </div>
            <p class="help">Telefoon, e-mail, website en adres komen uit <Link :href="route('settings.company')" style="color:var(--brand);font-weight:600;">Bedrijfsgegevens</Link>; logo en kleuren uit <Link :href="route('settings.brand')" style="color:var(--brand);font-weight:600;">Huisstijl</Link>.</p>
            <label class="toggle-row"><input type="checkbox" v-model="form.show_address"><div><div class="toggle-title">Adres tonen</div></div></label>
            <label class="toggle-row"><input type="checkbox" v-model="form.show_kvk"><div><div class="toggle-title">KvK-nummer tonen</div></div></label>
            <label class="toggle-row"><input type="checkbox" v-model="form.show_vat"><div><div class="toggle-title">Btw-nummer tonen</div></div></label>
          </div>
        </div>
      </div>

      <div class="vk-side">
        <div class="card">
          <div class="card-header"><div><div class="card-title">Voorbeeld</div></div></div>
          <div class="card-body">
            <div class="vk-preview" :style="{ background: `linear-gradient(160deg, ${company.brand_color} 0%, ${company.accent_color} 100%)` }">
              <div class="vk-card">
                <img v-if="company.logo" :src="company.logo" class="vk-logo" alt="" />
                <div v-else class="vk-mono" :style="{ background: company.brand_color }">{{ initial }}</div>
                <div class="vk-name">{{ form.contact_name || company.name }}</div>
                <div v-if="form.job_title" class="vk-role">{{ form.job_title }}</div>
                <div v-if="form.contact_name" class="vk-org" :style="{ color: company.brand_color }">{{ company.name }}</div>
                <div v-if="form.tagline" class="vk-tag">{{ form.tagline }}</div>
                <div class="vk-btn" :style="{ background: company.brand_color }">Opslaan in contacten</div>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div><div class="card-title">QR-code</div><div class="card-subtitle">Voor op je visitekaartje, offerte of bus.</div></div></div>
          <div class="card-body" style="text-align:center;">
            <img v-if="qr" :src="qr" class="vk-qr" alt="QR-code naar je visitekaartje" />
            <div v-if="qr"><a :href="qr" download="visitekaartje-qr.png" class="btn btn-secondary btn-sm" style="margin-top:10px;">Download PNG</a></div>
          </div>
        </div>
        <div v-if="site_published" class="card"><div class="card-body" style="font-size:13px;color:var(--text-2);">De knop "Website" op je kaartje verwijst naar je EasyInvoice-website.</div></div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.vk-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 18px; align-items: start; }
@media (max-width: 1000px) { .vk-layout { grid-template-columns: 1fr; } }
.slug-row { display: flex; align-items: stretch; border: 1px solid var(--border-strong); border-radius: 8px; overflow: hidden; background: var(--surface); }
.slug-prefix { padding: 9px 10px; font-size: 13px; color: var(--text-3); background: var(--surface-2); border-right: 1px solid var(--border); white-space: nowrap; }
.slug-input { flex: 1; border: 0; padding: 9px 10px; font: inherit; min-width: 0; outline: none; }
.link-actions { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
.help { font-size: 13px; color: var(--text-2); margin: 4px 0 12px; line-height: 1.5; }
.vk-preview { border-radius: 14px; padding: 26px 18px; }
.vk-card { background: #fff; border-radius: 18px; padding: 26px 20px; text-align: center; box-shadow: 0 18px 40px rgba(0,0,0,.22); }
.vk-logo { max-height: 56px; max-width: 160px; object-fit: contain; margin: 0 auto 10px; display: block; }
.vk-mono { width: 56px; height: 56px; border-radius: 14px; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 22px; margin: 0 auto 10px; }
.vk-name { font-weight: 800; font-size: 18px; color: #1c1917; }
.vk-role, .vk-tag { font-size: 13px; color: #57534e; margin-top: 2px; }
.vk-org { font-weight: 700; margin-top: 6px; font-size: 14px; }
.vk-btn { margin-top: 16px; color: #fff; border-radius: 10px; padding: 10px; font-weight: 600; font-size: 14px; }
.vk-qr { width: 200px; height: 200px; border: 1px solid var(--border); border-radius: 10px; }
</style>
