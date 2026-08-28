<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { eur } from '@/format.js';

defineProps({ ponto: Object });

const syncing = ref(false);
const syncNow = () => {
  syncing.value = true;
  router.post(route('bank.ponto.sync'), {}, { preserveScroll: true, onFinish: () => { syncing.value = false; } });
};
const disconnect = () => {
  if (confirm('Bankkoppeling verbreken? Al opgehaalde transacties blijven staan.')) {
    router.post(route('bank.ponto.disconnect'), {}, { preserveScroll: true });
  }
};
const toggleAccount = (account) => router.post(route('bank.ponto.account', account.id), {}, { preserveScroll: true });
</script>

<template>
  <!-- Bankkoppeling via Ponto: koppelen, status, rekeningen -->
  <div v-if="ponto && (ponto.available || ponto.connected)" class="card ponto-card">
    <div class="ponto-head">
      <div class="ponto-text">
        <div class="ponto-title">
          Automatische bankkoppeling
          <span v-if="ponto.sandbox" class="ponto-pill">testomgeving</span>
          <span v-else-if="ponto.connected && ponto.status === 'active'" class="ponto-pill ok">actief</span>
        </div>
        <div v-if="!ponto.connected" class="ponto-sub">
          Koppel je zakelijke rekening via Ponto: nieuwe transacties komen dan drie keer per dag vanzelf binnen — geen afschriften meer uploaden. Werkt met alle Nederlandse en Belgische banken.
        </div>
        <div v-if="!ponto.connected && ponto.price_label" class="ponto-price">{{ ponto.price_label }} — alleen voor rekeningen die je laat synchroniseren, bovenop je abonnement.</div>
        <div v-else class="ponto-sub">
          {{ ponto.last_synced_label ? `Laatst bijgewerkt ${ponto.last_synced_label}` : 'Nog niet bijgewerkt' }} · automatisch drie keer per dag.<span v-if="ponto.monthly_cost_label"> · {{ ponto.monthly_cost_label }}</span>
        </div>
      </div>
      <div class="ponto-actions">
        <template v-if="!ponto.connected">
          <a v-if="ponto.can_manage && ponto.can_connect" class="btn btn-primary" :href="route('bank.ponto.connect')">Bank koppelen</a>
          <template v-else-if="ponto.can_manage">
            <span class="ponto-muted">{{ ponto.connect_hint }}</span>
            <a class="btn btn-secondary btn-sm" :href="ponto.billing_url">Naar abonnement</a>
          </template>
          <span v-else class="ponto-muted">Alleen de eigenaar kan een bank koppelen.</span>
        </template>
        <template v-else>
          <a v-if="ponto.can_manage && ponto.status === 'needs_reauth'" class="btn btn-primary btn-sm" :href="route('bank.ponto.connect')">Opnieuw autoriseren</a>
          <button class="btn btn-secondary btn-sm" :disabled="syncing" @click="syncNow">{{ syncing ? 'Bezig…' : 'Nu bijwerken' }}</button>
          <button v-if="ponto.can_manage" class="btn btn-secondary btn-sm" @click="disconnect">Ontkoppelen</button>
        </template>
      </div>
    </div>

    <div v-if="ponto.status === 'needs_reauth'" class="ponto-alert">
      De bank vraagt om een nieuwe toestemming. Autoriseer de koppeling opnieuw om transacties te blijven ontvangen.
    </div>
    <div v-else-if="ponto.last_error" class="ponto-alert">{{ ponto.last_error }}</div>

    <div v-if="ponto.connected && ponto.accounts.length" class="ponto-accounts">
      <div v-for="a in ponto.accounts" :key="a.id" class="ponto-account" :class="{ off: !a.sync_enabled }">
        <div class="ponto-account-main">
          <div class="ponto-iban">{{ a.label }}</div>
          <div class="ponto-meta">
            {{ [a.bank_name, a.name].filter(Boolean).join(' · ') }}<span v-if="a.last_synced_label"> · bijgewerkt {{ a.last_synced_label }}</span>
          </div>
          <div v-if="a.reauth_soon" class="ponto-warn">Toestemming verloopt {{ a.reauth_label }} — autoriseer op tijd opnieuw.</div>
          <div v-if="a.last_error" class="ponto-warn">{{ a.last_error }}</div>
        </div>
        <div class="ponto-account-side">
          <div v-if="a.balance !== null" class="ponto-balance">{{ eur(a.balance) }}</div>
          <label class="ponto-toggle"><input type="checkbox" :checked="a.sync_enabled" @change="toggleAccount(a)"> synchroniseren</label>
        </div>
      </div>
    </div>
    <div v-else-if="ponto.connected" class="ponto-muted" style="margin-top:10px;">
      Nog geen rekeningen gevonden. Klik op "Nu bijwerken", of autoriseer opnieuw en kies een rekening.
    </div>
  </div>
</template>

<style scoped>
.ponto-card { margin-bottom: 14px; padding: 18px 20px; }
.ponto-head { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
.ponto-text { flex: 1 1 320px; min-width: 0; }
.ponto-title { font-family: var(--font-display); font-weight: 600; font-size: 16px; color: var(--text); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ponto-pill { font-size: 11px; font-weight: 600; line-height: 1; text-transform: uppercase; letter-spacing: .04em; padding: 4px 8px; border-radius: 999px; background: #fff4e5; color: #9a5b00; }
.ponto-pill.ok { background: #e8f7ee; color: #157347; }
.ponto-sub, .ponto-meta, .ponto-muted { font-size: 13px; color: var(--text-2); margin-top: 4px; line-height: 1.5; }
.ponto-price { font-size: 13px; color: var(--text); margin-top: 6px; font-weight: 500; }
.ponto-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.ponto-alert { margin-top: 12px; padding: 10px 12px; border-radius: 8px; background: #fff4e5; color: #9a5b00; font-size: 13px; }
.ponto-accounts { margin-top: 14px; border-top: 1px solid var(--border); }
.ponto-account { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); align-items: center; flex-wrap: wrap; }
.ponto-account.off { opacity: .6; }
.ponto-iban { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600; color: var(--text); }
.ponto-warn { font-size: 12px; color: #b42318; margin-top: 3px; }
.ponto-account-side { display: flex; align-items: center; gap: 14px; }
.ponto-balance { font-weight: 600; color: var(--text); font-variant-numeric: tabular-nums; }
.ponto-toggle { font-size: 12px; color: var(--text-2); display: flex; align-items: center; gap: 6px; cursor: pointer; }
</style>
