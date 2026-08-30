<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { eur } from '@/format';
import { t } from '@/i18n';

const page = usePage();
// Naam van de assistent per merk (EASY bij EasyInvoice, Lo bij Lopra) — zie config/brand.php.
const assistant = computed(() => page.props.brand?.assistant || 'EASY');
const initial = computed(() => assistant.value.charAt(0).toUpperCase());
const open = ref(false);
const messages = ref([]);
const initialized = ref(false);
const inputRef = ref(null);

const insights = computed(() => page.props.easy_insights || []);
const urgent = computed(() => insights.value.filter(i => ['danger', 'warning'].includes(i.severity)).length);

/**
 * Berichten markeren nadruk met **sterretjes** in plaats van HTML.
 * Hier splitsen we die op: de oneven stukken worden vet. Vue schrijft elk stuk
 * als tekst weg, dus een klantnaam met < of > kan nooit als code uitgevoerd
 * worden — met v-html zou dat wél kunnen.
 */
const parts = (text) => String(text ?? '').split('**');

const toggle = () => {
  open.value = !open.value;
  if (open.value && !initialized.value) {
    initialized.value = true;
    const userName = (page.props.auth?.user?.name || '').split(' ')[0] || t('daar');
    messages.value.push({
      from: 'bot',
      text: t('Hoi **:name**! Ik ben **:assistant**, je administratie-assistent. Vraag bijvoorbeeld naar **openstaand**, **achterstallig** of je **topklanten**.', { name: userName, assistant: assistant.value }),
    });
  }
};

const close = () => open.value = false;

const respond = (input) => {
  const q = input.toLowerCase().trim();
  const data = page.props.easy_data || {};

  if (/^(hoi|hallo|hai|hey|hi|hello|good (morning|afternoon|evening)|goedem|cześć|czesc|dzień|dzien|witam|siema)/.test(q)) {
    return t('Hoi! Waar kan ik je mee helpen?');
  }
  if (q.includes('openstaand') || q.includes('open ') || q.endsWith('open') || q.includes('do zapłaty') || q.includes('do zaplaty') || q.includes('nieopłac') || q.includes('nieoplac') || q.includes('otwart') || q.includes('outstanding') || q.includes('unpaid') || q.includes('receivable')) {
    return data.outstanding
      ? t('Er staat in totaal **:total** open, verdeeld over **:count** facturen.', { total: eur(data.outstanding.total), count: data.outstanding.count })
      : t('Geen openstaande facturen.');
  }
  if (q.includes('achterstall') || q.includes('te laat') || q.includes('przetermin') || q.includes('po terminie') || q.includes('zaległ') || q.includes('zalegl') || q.includes('overdue') || q.includes('late')) {
    return data.overdue
      ? t('Er zijn **:count** achterstallige facturen voor **:total**.', { count: data.overdue.count, total: eur(data.overdue.total) })
      : t('Geen achterstallige facturen op dit moment. 👌');
  }
  if (q.includes('incasso') || q.includes('armaere') || q.includes('windykac') || q.includes('creditline') || q.includes('collection') || q.includes('debt')) {
    return data.incasso
      ? t('Bij :partner liggen **:count** dossiers voor **:total**.', { partner: page.props.market?.incasso_partner || 'Armaere', count: data.incasso.count, total: eur(data.incasso.total) })
      : t('Geen actieve incasso-dossiers.');
  }
  if (q.includes('btw') || q.includes('aangifte') || q.includes('vat') || q.includes('deklarac') || q.includes('jpk') || q.includes('tax return')) {
    return data.vat
      ? t('Te dragen BTW voor Q:quarter: **:amount**.\nDeadline: :deadline.', { quarter: data.vat.quarter, amount: eur(data.vat.amount), deadline: data.vat.deadline })
      : t('BTW-gegevens niet beschikbaar.');
  }
  if (q.includes('top') || q.includes('beste klant') || q.includes('najlep') || q.includes('klienc') || q.includes('customer')) {
    if (data.top_customers?.length) {
      // Regels gescheiden door een newline; de bubbel toont die dankzij pre-wrap.
      return t('Top klanten dit jaar (excl. BTW):') + '\n' + data.top_customers.slice(0, 3)
        .map(c => `· ${c.name} — **${eur(c.total)}**`).join('\n');
    }
    return t('Nog geen omzetgegevens.');
  }
  if (q.includes('help') || q.includes('pomoc') || q === '?') {
    return t('Ik kan je informeren over: openstaand, achterstallig, incasso, BTW-aangifte, topklanten en omzet. Vraag het me in normale taal.');
  }
  return t('Daar weet ik nog niet veel over. Probeer een vraag over **openstaand**, **achterstallig**, **incasso**, **BTW** of **topklanten**.');
};

const send = (text) => {
  text = text.trim();
  if (!text) return;
  messages.value.push({ from: 'user', text });
  messages.value.push({ from: 'bot', text: respond(text) });
  if (inputRef.value) inputRef.value.value = '';
};

const quick = (q) => send(q);
</script>

<template>
  <button class="easy-fab" @click="toggle" :title="$t('Vraag :assistant iets', { assistant })">
    {{ initial }}
    <span v-if="urgent > 0 && !open" class="pulse">{{ urgent }}</span>
  </button>

  <div v-if="open" class="overlay" @click="close"></div>

  <div class="panel" :class="{ open }">
    <div class="header">
      <div class="identity">
        <div class="avatar">{{ initial }}</div>
        <div>
          <div class="name">{{ assistant }}</div>
          <div class="tagline">{{ $t('Je administratie-assistent') }}</div>
        </div>
      </div>
      <button class="close" @click="close">×</button>
    </div>

    <div class="body">
      <div v-if="insights.length" class="insights">
        <div class="section-label">{{ $t('Wat ik vandaag voor je zie') }}</div>
        <div v-for="ins in insights" :key="ins.title" class="insight" :class="ins.severity">
          <div class="ins-title">{{ ins.title }}</div>
          <div class="ins-detail" style="white-space: pre-wrap;">
            <template v-for="(part, p) in parts(ins.detail)" :key="p">
              <b v-if="p % 2" class="amt">{{ part }}</b><template v-else>{{ part }}</template>
            </template>
          </div>
        </div>
      </div>

      <div v-if="messages.length" class="messages">
        <div v-for="(m, i) in messages" :key="i" class="msg" :class="m.from">
          <div v-if="m.from === 'bot'" class="msg-avatar">{{ initial }}</div>
          <div class="bubble" style="white-space: pre-wrap;">
            <template v-for="(part, p) in parts(m.text)" :key="p">
              <b v-if="p % 2">{{ part }}</b><template v-else>{{ part }}</template>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div class="quick">
      <button class="chip" @click="quick($t('Hoeveel staat er open?'))">{{ $t('Openstaand') }}</button>
      <button class="chip" @click="quick($t('Welke facturen zijn achterstallig?'))">{{ $t('Achterstallig') }}</button>
      <button class="chip" @click="quick($t('BTW deze kwartaal?'))">{{ $t('BTW') }}</button>
      <button class="chip" @click="quick($t('Topklanten?'))">{{ $t('Topklanten') }}</button>
    </div>

    <div class="input-row">
      <input ref="inputRef" type="text" :placeholder="$t('Stel een vraag aan :assistant...', { assistant })"
        @keydown.enter="send($event.target.value)" />
      <button class="send-btn" @click="send(inputRef.value)">→</button>
    </div>
  </div>
</template>

<style scoped>
.easy-fab { position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--brand), var(--brand-dark)); color: white; border: 2px solid white; box-shadow: 0 8px 24px rgba(232,35,31,.35); cursor: pointer; z-index: 90; font-family: var(--font-display); font-weight: 700; font-size: 20px; }
.pulse { position: absolute; top: -2px; right: -2px; min-width: 20px; height: 20px; padding: 0 5px; background: #FCD34D; color: #1F2937; border-radius: 100px; border: 2px solid white; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
.overlay { position: fixed; inset: 0; background: rgba(28,25,23,.3); z-index: 100; }
.panel { position: fixed; top: 0; right: 0; width: min(420px, 100vw); height: 100vh; background: var(--bg); z-index: 101; display: flex; flex-direction: column; box-shadow: -10px 0 40px rgba(28,25,23,.12); transform: translateX(100%); transition: transform .25s; }
.panel.open { transform: translateX(0); }
.header { padding: 18px 20px; background: linear-gradient(135deg, var(--brand), var(--brand-dark)); color: white; display: flex; align-items: center; justify-content: space-between; }
.identity { display: flex; align-items: center; gap: 12px; }
.avatar { width: 44px; height: 44px; background: white; color: var(--brand); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 22px; }
.name { font-family: var(--font-display); font-weight: 700; font-size: 17px; }
.tagline { font-size: 12px; opacity: .9; }
.close { background: rgba(255,255,255,.15); color: white; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 20px; }
.body { flex: 1; overflow-y: auto; padding: 16px 18px; }
.section-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; margin: 4px 4px 10px; }
.insights { display: flex; flex-direction: column; gap: 8px; margin-bottom: 22px; }
.insight { background: var(--surface); border: 1px solid var(--border); border-left: 3px solid var(--info); border-radius: 10px; padding: 12px 14px; }
.insight.warning { border-left-color: var(--warning); }
.insight.danger { border-left-color: var(--brand); }
.insight.success { border-left-color: var(--success); }
.ins-title { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
.ins-detail { font-size: 12px; color: var(--text-2); line-height: 1.45; }
.messages { display: flex; flex-direction: column; gap: 10px; }
.msg { display: flex; gap: 8px; max-width: 88%; }
.msg.bot { align-self: flex-start; }
.msg.user { align-self: flex-end; flex-direction: row-reverse; }
.msg-avatar { width: 26px; height: 26px; background: var(--brand); color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0; }
.bubble { padding: 10px 14px; border-radius: 14px; font-size: 13px; line-height: 1.5; background: var(--surface); border: 1px solid var(--border); }
.msg.user .bubble { background: var(--brand); color: white; border-color: var(--brand); }
.quick { display: flex; gap: 6px; flex-wrap: wrap; padding: 8px 18px; }
.chip { padding: 6px 11px; font-size: 12px; border: 1px solid var(--border); background: var(--surface); border-radius: 100px; cursor: pointer; }
.input-row { padding: 12px 18px 18px; border-top: 1px solid var(--border); background: var(--surface); display: flex; gap: 8px; }
.input-row input { flex: 1; height: 40px; padding: 0 14px; border: 1px solid var(--border); border-radius: 100px; }
.send-btn { width: 40px; height: 40px; border-radius: 50%; background: var(--brand); color: white; border: none; cursor: pointer; }
</style>
