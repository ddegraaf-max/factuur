<script setup>
import { ref } from 'vue';
import { router, Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  enabled: Boolean,
  activated_at: String,
  backup_codes: Array,
});

const showDisable = ref(false);
const disableForm = useForm({ password: '' });

const startSetup = () => router.post(route('settings.security.setup'));
const disable = () => disableForm.delete(route('settings.security.disable'), { onSuccess: () => showDisable.value = false });
const regenerateCodes = () => router.post(route('settings.security.recovery'), {}, { preserveScroll: true });
const copyCodes = () => {
  navigator.clipboard.writeText(props.backup_codes.join('\n'));
};
</script>

<template>
  <Head title="Beveiliging" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">Beveiliging</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Beveiliging</h1>
        <p class="page-subtitle">Beheer je inlogbeveiliging en accountveiligheid</p>
      </div>
    </div>

    <div class="security-status-card" :class="{ active: enabled }">
      <div class="security-status-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
          <polyline v-if="enabled" points="9 12 11 14 15 10" />
        </svg>
      </div>
      <div>
        <div class="status-title">
          Tweestapsverificatie
          <span class="pill" :class="enabled ? 'pill-paid' : 'pill-draft'">{{ enabled ? 'Actief' : 'Inactief' }}</span>
        </div>
        <div class="status-detail">
          <span v-if="enabled">Geactiveerd. Bij elke nieuwe login vraagt EasyInvoice een 6-cijferige code uit je authenticator app.</span>
          <span v-else>Voeg een extra beveiligingslaag toe. Naast je wachtwoord vraagt EasyInvoice om een tijdelijke code uit een authenticator app.</span>
        </div>
      </div>
      <div>
        <button v-if="!enabled" class="btn btn-primary btn-sm" @click="startSetup">Activeren</button>
        <button v-else class="btn btn-secondary btn-sm" @click="showDisable = true">Deactiveren</button>
      </div>
    </div>

    <div v-if="enabled" class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Backup codes</div>
          <div class="card-subtitle">{{ backup_codes.length }} codes beschikbaar — gebruik er één als je geen toegang hebt tot je authenticator</div>
        </div>
        <button class="btn btn-secondary btn-sm" @click="regenerateCodes">Nieuwe genereren</button>
      </div>
      <div class="card-body">
        <div class="backup-codes">
          <div v-for="(c, i) in backup_codes" :key="c" class="backup-code">
            <span class="n">{{ String(i+1).padStart(2,'0') }}</span> {{ c }}
          </div>
        </div>
        <button class="btn btn-ghost btn-sm" @click="copyCodes" style="margin-top:12px;">Kopiëren naar klembord</button>
        <div class="footnote">Tip: print uit of bewaar in je wachtwoordmanager. Elke code werkt eenmalig.</div>
      </div>
    </div>

    <!-- Disable confirm modal -->
    <div v-if="showDisable" class="modal-overlay" @click.self="showDisable = false">
      <div class="modal">
        <div class="modal-header"><div class="modal-title">Tweestapsverificatie uitschakelen?</div></div>
        <div class="modal-body">
          <p>Bevestig met je wachtwoord om door te gaan.</p>
          <input type="password" v-model="disableForm.password" placeholder="Huidig wachtwoord" />
          <div v-if="disableForm.errors.password" class="field-error">{{ disableForm.errors.password }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="showDisable = false">Annuleren</button>
          <button class="btn btn-danger btn-sm" @click="disable" :disabled="disableForm.processing">Uitschakelen</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.security-status-card { display: grid; grid-template-columns: 72px 1fr auto; gap: 22px; align-items: center; padding: 22px 26px; background: var(--surface); border: 1px solid var(--border); border-left: 4px solid var(--text-4); border-radius: 12px; margin-bottom: 18px; }
.security-status-card.active { border-left-color: var(--success); background: linear-gradient(180deg, var(--success-bg) 0%, var(--surface) 100%); }
.security-status-icon { width: 64px; height: 64px; border-radius: 16px; background: var(--surface-2); color: var(--text-4); display: inline-flex; align-items: center; justify-content: center; }
.security-status-card.active .security-status-icon { background: var(--success-bg); color: var(--success); }
.security-status-icon svg { width: 32px; height: 32px; }
.status-title { font-family: var(--font-display); font-weight: 600; font-size: 19px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
.status-detail { font-size: 13px; color: var(--text-3); }
.backup-codes { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 22px; padding: 18px 22px; background: var(--surface-2); border-radius: 10px; }
.backup-code { font-family: var(--font-mono); font-size: 15px; font-weight: 600; }
.backup-code .n { color: var(--text-4); font-weight: 400; margin-right: 6px; }
.footnote { font-size: 12px; color: var(--text-3); margin-top: 10px; }
</style>
