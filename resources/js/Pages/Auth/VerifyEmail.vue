<script setup>
import { useForm, Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const props = defineProps({
  email: String,
  canResendIn: { type: Number, default: 0 },
});

const page = usePage();
const flash = computed(() => page.props.flash?.flash || null);

const digits = ref(['', '', '', '', '', '']);
const inputs = ref([]);

const form = useForm({ code: '' });
const resendForm = useForm({});

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
  nextTick(() => inputs.value[0]?.focus());
});

onBeforeUnmount(() => clearInterval(timer));

const onInput = (idx, e) => {
  const val = e.target.value.replace(/\D/g, '');
  if (val.length === 0) {
    digits.value[idx] = '';
    return;
  }
  // If user pasted multiple digits, distribute across inputs
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
  const next = Math.min(clean.length, 5);
  inputs.value[next]?.focus();
  maybeSubmit();
};

const maybeSubmit = () => {
  if (digits.value.every(d => d.length === 1)) submit();
};

const submit = () => {
  form.code = digits.value.join('');
  form.post(route('verification.verify'), {
    preserveScroll: true,
    onError: () => {
      // Clear inputs and refocus for retry
      digits.value = ['', '', '', '', '', ''];
      nextTick(() => inputs.value[0]?.focus());
    },
  });
};

const resend = () => {
  if (cooldown.value > 0) return;
  resendForm.post(route('verification.resend'), {
    preserveScroll: true,
    onSuccess: () => startCooldown(60),
  });
};

const logout = () => {
  router.post(route('logout'));
};
</script>

<template>
  <Head title="E-mail bevestigen" />
  <AuthLayout>
    <template #hero>
      <h2>Bevestig je e-mailadres</h2>
      <p>We hebben een 6-cijferige code gestuurd. Voer 'm hieronder in om je account te activeren.</p>
      <ul class="hero-bullets">
        <li>Code is 15 minuten geldig</li>
        <li>Check ook je spam-map</li>
        <li>Nieuwe code? Vraag 'm hieronder aan</li>
      </ul>
    </template>

    <div class="login-form-card verify-card">
      <div class="login-form-title">Bevestig je e-mailadres</div>
      <div class="login-form-sub">
        We stuurden een code naar <strong>{{ email }}</strong>.
      </div>

      <div v-if="flash" class="status-message">{{ flash }}</div>

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
          {{ form.processing ? 'Bezig…' : 'Bevestigen' }}
        </button>
      </form>

      <div class="verify-actions">
        <button type="button" class="link-btn" :disabled="cooldown > 0 || resendForm.processing" @click="resend">
          <span v-if="cooldown > 0">Nieuwe code over {{ cooldown }}s</span>
          <span v-else-if="resendForm.processing">Versturen…</span>
          <span v-else>Stuur nieuwe code</span>
        </button>
        <span class="verify-sep">·</span>
        <button type="button" class="link-btn" @click="logout">Andere e-mail gebruiken</button>
      </div>
    </div>
  </AuthLayout>
</template>
