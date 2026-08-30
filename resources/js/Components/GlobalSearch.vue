<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const open = ref(false);
const q = ref('');
const groups = ref([]);
const actions = ref([]);
const active = ref(0);
const loading = ref(false);
const input = ref(null);
let timer = null;
let controller = null;

const flat = computed(() => {
  const items = [];
  for (const g of groups.value) for (const it of g.items) items.push({ ...it, group: g.title });
  for (const a of actions.value) items.push({ ...a, group: 'Acties', icon: 'bolt' });
  return items;
});

const show = () => { open.value = true; nextTick(() => input.value?.focus()); if (!q.value) search(); };
const hide = () => { open.value = false; };
const go = (item) => { if (!item) return; hide(); router.visit(item.url); };

const search = () => {
  clearTimeout(timer);
  timer = setTimeout(async () => {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    try {
      const res = await fetch(route('search', { q: q.value }), { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal });
      const data = await res.json();
      groups.value = data.groups || [];
      actions.value = data.actions || [];
      active.value = 0;
    } catch (e) { /* afgebroken of offline */ } finally { loading.value = false; }
  }, 160);
};
watch(q, search);

const onKey = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open.value ? hide() : show(); return; }
  if (!open.value) return;
  if (e.key === 'Escape') { hide(); }
  if (e.key === 'ArrowDown') { e.preventDefault(); active.value = Math.min(active.value + 1, flat.value.length - 1); }
  if (e.key === 'ArrowUp') { e.preventDefault(); active.value = Math.max(active.value - 1, 0); }
  if (e.key === 'Enter') { e.preventDefault(); go(flat.value[active.value]); }
};
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);
</script>

<template>
  <button type="button" class="gs-trigger" @click="show" :title="$t('Zoeken (Ctrl+K)')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <span class="gs-trigger-text">{{ $t('Zoeken…') }}</span>
    <kbd class="gs-kbd">{{ isMac ? '⌘' : 'Ctrl' }} K</kbd>
  </button>

  <Teleport to="body">
    <div v-if="open" class="gs-overlay" @mousedown.self="hide">
      <div class="gs-panel" role="dialog" :aria-label="$t('Zoeken')">
        <div class="gs-input-row">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input ref="input" v-model="q" type="text" :placeholder="$t('Zoek factuur, offerte, klant, product… of typ een actie')" autocomplete="off" spellcheck="false">
          <span v-if="loading" class="gs-spinner"></span>
          <kbd class="gs-kbd" @click="hide">Esc</kbd>
        </div>
        <div class="gs-results">
          <template v-if="flat.length">
            <template v-for="(g, gi) in groups" :key="'g' + gi">
              <div class="gs-group">{{ g.title }}</div>
              <button v-for="it in g.items" :key="it.url" type="button" class="gs-item" :class="{ active: flat[active]?.url === it.url && flat[active]?.group === g.title }" @mouseenter="active = flat.findIndex(f => f.url === it.url && f.group === g.title)" @click="go(it)">
                <span class="gs-item-title">{{ it.title }}</span>
                <span class="gs-item-sub">{{ it.subtitle }}</span>
              </button>
            </template>
            <template v-if="actions.length">
              <div class="gs-group">{{ $t("Acties & pagina's") }}</div>
              <button v-for="a in actions" :key="'a' + a.url" type="button" class="gs-item gs-action" :class="{ active: flat[active]?.url === a.url && flat[active]?.group === 'Acties' }" @mouseenter="active = flat.findIndex(f => f.url === a.url && f.group === 'Acties')" @click="go(a)">
                <span class="gs-item-title">→ {{ a.title }}</span>
              </button>
            </template>
          </template>
          <div v-else class="gs-empty">{{ q.length < 2 ? $t('Typ minimaal twee tekens — bijvoorbeeld een factuurnummer, klantnaam of "nieuwe offerte".') : (loading ? $t('Zoeken…') : $t('Niets gevonden.')) }}</div>
        </div>
        <div class="gs-foot"><span>{{ $t('↑↓ kiezen') }}</span><span>{{ $t('↵ openen') }}</span><span>{{ $t('Esc sluiten') }}</span></div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.gs-trigger { display: inline-flex; align-items: center; gap: 8px; height: 34px; padding: 0 10px 0 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); color: var(--text-3); font-size: 13px; cursor: pointer; min-width: 200px; }
.gs-trigger:hover { border-color: var(--border-strong); color: var(--text-2); }
.gs-trigger svg { width: 15px; height: 15px; flex: none; }
.gs-trigger-text { flex: 1; text-align: left; }
.gs-kbd { font-family: inherit; font-size: 11px; color: var(--text-4); border: 1px solid var(--border); border-radius: 5px; padding: 1px 6px; background: var(--surface-2); white-space: nowrap; }
.gs-overlay { position: fixed; inset: 0; background: rgba(28,25,23,0.45); z-index: 1000; display: flex; align-items: flex-start; justify-content: center; padding: 10vh 16px 16px; }
.gs-panel { width: 100%; max-width: 640px; background: var(--surface); border-radius: 14px; box-shadow: 0 24px 60px rgba(0,0,0,0.28); overflow: hidden; border: 1px solid var(--border); }
.gs-input-row { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--border); }
.gs-input-row svg { width: 18px; height: 18px; color: var(--text-3); flex: none; }
.gs-input-row input { flex: 1; border: none; outline: none; font-size: 16px; background: transparent; color: var(--text); padding: 6px 0; }
.gs-spinner { width: 14px; height: 14px; border: 2px solid var(--border); border-top-color: var(--brand); border-radius: 50%; animation: gs-spin 0.7s linear infinite; }
@keyframes gs-spin { to { transform: rotate(360deg); } }
.gs-results { max-height: 60vh; overflow-y: auto; padding: 6px; }
.gs-group { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-4); font-weight: 700; padding: 10px 10px 4px; }
.gs-item { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; width: 100%; text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: none; cursor: pointer; color: var(--text); }
.gs-item.active, .gs-item:hover { background: var(--brand-tint); }
.gs-item-title { font-weight: 600; font-size: 14px; }
.gs-item-sub { font-size: 12.5px; color: var(--text-3); }
.gs-action .gs-item-title { font-weight: 500; }
.gs-empty { padding: 26px 16px; text-align: center; color: var(--text-3); font-size: 13.5px; line-height: 1.6; }
.gs-foot { display: flex; gap: 16px; padding: 8px 16px; border-top: 1px solid var(--border); font-size: 11.5px; color: var(--text-4); background: var(--surface-2); }
@media (max-width: 720px) { .gs-trigger { min-width: 0; } .gs-trigger-text, .gs-trigger .gs-kbd { display: none; } }
</style>
