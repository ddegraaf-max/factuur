<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';

const props = defineProps({
  maskedEmail: String,
  codeSent: { type: Boolean, default: false },
  canResendIn: { type: Number, default: 0 },
  // Kwam de bezoeker via een factuurlink? Dan tonen we die context.
  gate: { type: Object, default: null },
});

const digits = ref(['', '', '', '', '', '']);
const inputs = ref([]);

const form = useForm({ code: '' });
const sendForm = useForm({});

const cooldown = ref(props.canResendIn || 0);
let timer = null;

const startCooldown = (seconds) => {
  cooldown.value = seconds;
  clearInterval(timer);
  timer = setInterval(() => {
    if (cooldown.value > 0) cooldown.value--;
    else clearInterval(timer);
  }, 1000);
};

onMounted(() => {
  if (cooldown.value > 0) startCooldown(cooldown.value);
  if (props.codeSent) nextTick(() => inputs.value[0]?.focus());
});

onBeforeUnmount(() => clearInterval(timer));

const onInput = (idx, e) => {
  const val = e.target.value.replace(/\D/g, '');
  if (val.length === 0) {
    digits.value[idx] = '';
    return;
  }
  if (val.length > 1) {
    const chars = val.slice(0, 6 - idx).split('');
    chars.forEach((c, i) => { digits.value[idx + i] = c; });
    const next = Math.min(idx + chars.length, 5);
    inputs.value[next]?.focus();
    maybeSubmit();
    return;
  }
  digits.value[idx] = val;
  if (idx < 5) inputs.value[idx + 1]?.focus();
  maybeSubmit();
};

const onKeydown = (idx, e) => {
  if (e.key === 'Backspace' && !digits.value[idx] && idx > 0) {
    inputs.value[idx - 1]?.focus();
    digits.value[idx - 1] = '';
    e.preventDefault();
  }
  if (e.key === 'ArrowLeft' && idx > 0) inputs.value[idx - 1]?.focus();
  if (e.key === 'ArrowRight' && idx < 5) inputs.value[idx + 1]?.focus();
};

const onPaste = (e) => {
  const text = (e.clipboardData || window.clipboardData).getData('text');
  const clean = text.replace(/\D/g, '').slice(0, 6);
  if (clean.length === 0) return;
  e.preventDefault();
  clean.split('').forEach((c, i) => { digits.value[i] = c; });
  inputs.value[Math.min(clean.length, 5)]?.focus();
  maybeSubmit();
};

const maybeSubmit = () => {
  if (digits.value.every(d => d.length === 1)) submit();
};

const submit = () => {
  form.code = digits.value.join('');
  form.post(route('portal.verify'), {
    preserveScroll: true,
    onError: () => {
      digits.value = ['', '', '', '', '', ''];
      nextTick(() => inputs.value[0]?.focus());
    },
  });
};

const sendCode = () => {
  if (cooldown.value > 0) return;
  sendForm.post(route('portal.code.send'), {
    preserveScroll: true,
    onSuccess: () => {
      startCooldown(60);
      nextTick(() => inputs.value[0]?.focus());
    },
  });
};
</script>

<template>
  <Head title="Verificatie · Facturenportaal" />
  <PortalLayout>
    <div class="portal-center">
      <div class="portal-card portal-verify-card">
        <div v-if="gate" class="portal-gate-context">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <div>
            <div class="portal-gate-number">Factuur {{ gate.number }}</div>
            <div v-if="gate.company" class="portal-gate-company">van {{ gate.company }}</div>
          </div>
        </div>

        <h1 class="portal-card-title">Bevestig dat jij het bent</h1>
        <p class="portal-card-sub">
          <template v-if="codeSent">
            We hebben een 6-cijferige code gestuurd naar <strong>{{ maskedEmail }}</strong>.
            Voer die hieronder in.
          </template>
          <template v-else>
            Voor je veiligheid sturen we eerst een eenmalige toegangscode naar
            <strong>{{ maskedEmail }}</strong> — het e-mailadres waarop deze factuur is ontvangen.
          </template>
        </p>

        <template v-if="codeSent">
          <form @submit.prevent="submit">
            <div class="otp-group" @paste="onPaste">
              <input
                v-for="(d, idx) in digits"
                :key="idx"
                :ref="el => inputs[idx] = el"
                :value="d"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="1"
                class="otp-input"
                :class="{ 'has-error': form.errors.code }"
                @input="onInput(idx, $event)"
                @keydown="onKeydown(idx, $event)"
              />
            </div>

            <div v-if="form.errors.code" class="field-error otp-error">{{ form.errors.code }}</div>

            <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing || digits.some(d => !d)">
              {{ form.processing ? 'Controleren…' : 'Toegang krijgen' }}
            </button>
          </form>

          <div class="verify-actions">
            <button type="button" class="link-btn" :disabled="cooldown > 0 || sendForm.processing" @click="sendCode">
              <span v-if="cooldown > 0">Nieuwe code over {{ cooldown }}s</span>
              <span v-else-if="sendForm.processing">Versturen…</span>
              <span v-else>Stuur nieuwe code</span>
            </button>
            <span class="verify-sep">·</span>
            <Link :href="route('portal.login')" class="link-btn">Ander e-mailadres</Link>
          </div>
        </template>

        <template v-else>
          <div v-if="sendForm.errors.code" class="field-error" style="margin-bottom:12px;">{{ sendForm.errors.code }}</div>
          <button class="btn btn-primary btn-block" :disabled="sendForm.processing" @click="sendCode">
            {{ sendForm.processing ? 'Versturen…' : 'Stuur toegangscode' }}
          </button>
          <p class="portal-verify-note">
            De code is 10 minuten geldig. Check ook je spam-map.
          </p>
        </template>
      </div>
    </div>
  </PortalLayout>
</template>

<style scoped>
.portal-center {
  display: flex;
  justify-content: center;
  padding-top: 24px;
}
.portal-verify-card { max-width: 460px; }
.portal-gate-context {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 12px 16px;
  margin-bottom: 22px;
}
.portal-gate-context svg { width: 22px; height: 22px; color: var(--text-3); flex: none; }
.portal-gate-number { font-weight: 600; font-size: 14px; }
.portal-gate-company { font-size: 12.5px; color: var(--text-3); }
.portal-verify-note {
  margin-top: 14px;
  font-size: 12.5px;
  color: var(--text-4);
  text-align: center;
}
</style>
