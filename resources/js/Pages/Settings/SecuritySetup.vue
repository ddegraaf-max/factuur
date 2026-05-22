<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  step: String, // 'qr' | 'backup'
  secret: String,
  otpauth_url: String,
  backup_codes: Array,
});

const form = useForm({ code: '' });
const codeDigits = ref(['', '', '', '', '', '']);

onMounted(() => {
  if (props.step === 'qr') {
    nextTick(() => document.getElementById('pin-0')?.focus());
  }
});

const onInput = (idx, e) => {
  const v = e.target.value.replace(/\D/g, '').slice(0, 1);
  codeDigits.value[idx] = v;
  e.target.value = v;
  if (v && idx < 5) document.getElementById(`pin-${idx+1}`)?.focus();
};
const onKey = (idx, e) => {
  if (e.key === 'Backspace' && !codeDigits.value[idx] && idx > 0) {
    document.getElementById(`pin-${idx-1}`)?.focus();
  }
};

const verify = () => {
  form.code = codeDigits.value.join('');
  if (form.code.length !== 6) return;
  form.post(route('settings.security.verify'));
};

const finish = () => router.visit(route('settings.security'));
const cancel = () => router.delete(route('settings.security.disable'));

const copyCodes = () => navigator.clipboard.writeText(props.backup_codes.join('\n'));
</script>

<template>
  <Head title="Beveiliging instellen" />
  <AppLayout>
    <template #breadcrumb>Instellingen / Beveiliging / <span class="breadcrumb-current">Activeren</span></template>

    <div class="single-col">
      <div class="card">
        <div class="card-body">
          <div class="wizard-stepper">
            <div class="step" :class="{ active: step === 'qr' }">
              <div class="num">1</div><span>Scan QR-code</span>
            </div>
            <div class="div"></div>
            <div class="step" :class="{ active: step === 'qr' }">
              <div class="num">2</div><span>Verifiëren</span>
            </div>
            <div class="div"></div>
            <div class="step" :class="{ active: step === 'backup' }">
              <div class="num">3</div><span>Backup codes</span>
            </div>
          </div>

          <div v-if="step === 'qr'" class="wizard-content">
            <h2>Koppel je authenticator app</h2>
            <p class="muted">Scan de QR-code met Google Authenticator, Authy of 1Password — of typ de geheime code handmatig over.</p>

            <div class="qr-layout">
              <div class="qr-box">
                <!-- Use Google Charts QR API for real scan-ability (no JS deps needed) -->
                <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(otpauth_url)}`" alt="QR code" />
              </div>
              <div class="qr-instructions">
                <ol>
                  <li>Download een authenticator-app uit je app store</li>
                  <li>Tik op "Account toevoegen" / "+"</li>
                  <li>Scan deze QR met je telefoon</li>
                  <li>Voer hieronder de 6-cijferige code in</li>
                </ol>
                <div class="manual-key">
                  <div class="key-label">Of voer handmatig in:</div>
                  <div class="key-value">{{ secret }}</div>
                </div>
              </div>
            </div>

            <div style="margin-top:30px;text-align:center;">
              <div class="pin-input">
                <input v-for="i in 6" :key="i"
                  :id="`pin-${i-1}`"
                  type="text" inputmode="numeric" maxlength="1"
                  @input="onInput(i-1, $event)"
                  @keydown="onKey(i-1, $event)" />
              </div>
              <div v-if="form.errors.code" class="field-error">{{ form.errors.code }}</div>
            </div>

            <div class="wizard-footer">
              <button class="btn btn-secondary" @click="cancel">Annuleren</button>
              <button class="btn btn-primary" @click="verify" :disabled="form.processing">Verifiëren</button>
            </div>
          </div>

          <div v-else-if="step === 'backup'" class="wizard-content">
            <div class="success-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h2 style="text-align:center;">Bijna klaar — bewaar je backup codes</h2>
            <p class="muted" style="text-align:center;">Bewaar deze codes op een veilige plek. Je kunt ze gebruiken om in te loggen als je geen toegang hebt tot je authenticator.</p>

            <div class="backup-warning">
              <b>Belangrijk:</b> deze codes worden maar één keer getoond. Elke code werkt eenmalig.
            </div>

            <div class="backup-codes">
              <div v-for="(c, i) in backup_codes" :key="c" class="backup-code">
                <span class="n">{{ String(i+1).padStart(2,'0') }}</span> {{ c }}
              </div>
            </div>

            <div style="margin-top:14px;">
              <button class="btn btn-secondary btn-sm" @click="copyCodes">Kopiëren</button>
            </div>

            <div class="wizard-footer">
              <button class="btn btn-primary" @click="finish">Codes bewaard — voltooien</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.wizard-stepper { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }
.step { display: flex; align-items: center; gap: 10px; color: var(--text-4); font-size: 13px; font-weight: 500; }
.step .num { width: 28px; height: 28px; border-radius: 50%; background: var(--surface-2); color: var(--text-3); font-weight: 700; display: inline-flex; align-items: center; justify-content: center; border: 2px solid var(--border); }
.step.active { color: var(--text); }
.step.active .num { background: var(--brand); color: white; border-color: var(--brand); }
.div { flex: 1; height: 2px; background: var(--border); }
.qr-layout { display: grid; grid-template-columns: 220px 1fr; gap: 28px; align-items: center; padding: 20px 0; }
.qr-box { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px; }
.qr-box img { width: 100%; }
.qr-instructions ol { padding-left: 18px; }
.manual-key { padding: 14px 16px; background: var(--surface-2); border-radius: 8px; margin-top: 14px; }
.key-label { font-size: 12px; color: var(--text-3); margin-bottom: 4px; }
.key-value { font-family: var(--font-mono); font-weight: 600; letter-spacing: 0.06em; word-break: break-all; }
.pin-input { display: inline-flex; gap: 10px; }
.pin-input input { width: 52px; height: 60px; text-align: center; font-size: 26px; font-weight: 600; border: 2px solid var(--border); border-radius: 12px; }
.pin-input input:focus { border-color: var(--brand); outline: none; }
.wizard-footer { display: flex; justify-content: space-between; margin-top: 28px; gap: 8px; }
.success-icon { width: 80px; height: 80px; background: var(--success-bg); color: var(--success); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
.success-icon svg { width: 40px; height: 40px; }
.backup-warning { padding: 14px 16px; background: #FEF3C7; border-left: 3px solid #F59E0B; border-radius: 4px; font-size: 13px; margin: 16px 0; }
.backup-codes { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 22px; padding: 18px 22px; background: var(--surface-2); border-radius: 10px; }
.backup-code { font-family: var(--font-mono); font-size: 15px; font-weight: 600; }
.backup-code .n { color: var(--text-4); font-weight: 400; margin-right: 6px; }
</style>
