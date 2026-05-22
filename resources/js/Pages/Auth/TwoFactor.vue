<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const useBackup = ref(false);
const codeDigits = ref(['', '', '', '', '', '']);

const form = useForm({
  code: '',
  recovery_code: '',
});

onMounted(() => {
  nextTick(() => document.getElementById('pin-0')?.focus());
});

const onInput = (idx, event) => {
  const val = event.target.value.replace(/\D/g, '').slice(0, 1);
  codeDigits.value[idx] = val;
  event.target.value = val;
  if (val && idx < 5) document.getElementById(`pin-${idx+1}`)?.focus();
  if (idx === 5 && val) submit();
};
const onKeydown = (idx, event) => {
  if (event.key === 'Backspace' && !codeDigits.value[idx] && idx > 0) {
    document.getElementById(`pin-${idx-1}`)?.focus();
  }
};
const onPaste = (e) => {
  e.preventDefault();
  const text = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
  for (let i = 0; i < 6; i++) {
    codeDigits.value[i] = text[i] || '';
    const el = document.getElementById(`pin-${i}`);
    if (el) el.value = text[i] || '';
  }
  document.getElementById(`pin-${Math.min(text.length, 5)}`)?.focus();
};

const submit = () => {
  if (useBackup.value) {
    form.transform(d => ({ recovery_code: d.recovery_code }))
        .post(route('two-factor.challenge'));
  } else {
    form.code = codeDigits.value.join('');
    if (form.code.length !== 6) return;
    form.transform(d => ({ code: d.code }))
        .post(route('two-factor.challenge'));
  }
};
</script>

<template>
  <Head title="Verificatie" />
  <AuthLayout>
    <template #hero>
      <h2>Twee stappen veilig</h2>
      <p>Je tweestapsverificatie is actief. Voer de code uit je authenticator app in om verder te gaan.</p>
    </template>

    <div class="login-form-card">
      <div class="login-form-title">{{ useBackup ? 'Backup-code' : 'Verificatiecode' }}</div>
      <div class="login-form-sub">
        {{ useBackup ? 'Voer een van je backup-codes in.' : 'Open je authenticator app en voer de 6-cijferige code in.' }}
      </div>

      <form @submit.prevent="submit">
        <div v-if="!useBackup" class="pin-input" @paste="onPaste">
          <input v-for="i in 6" :key="i"
            :id="`pin-${i-1}`"
            type="text"
            inputmode="numeric"
            maxlength="1"
            pattern="[0-9]"
            @input="onInput(i-1, $event)"
            @keydown="onKeydown(i-1, $event)" />
        </div>
        <div v-else class="form-group">
          <label>Backup-code</label>
          <input v-model="form.recovery_code" type="text" placeholder="0000-0000" />
        </div>

        <div v-if="form.errors.code" class="field-error">{{ form.errors.code }}</div>
        <div v-if="form.errors.recovery_code" class="field-error">{{ form.errors.recovery_code }}</div>

        <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
          Verifiëren
        </button>

        <div class="login-bottom">
          <a @click.prevent="useBackup = !useBackup; form.reset();">
            {{ useBackup ? '← Terug naar code' : 'Geen toegang? Gebruik backup-code' }}
          </a>
        </div>
      </form>
    </div>
  </AuthLayout>
</template>
