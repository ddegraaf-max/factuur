<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const page = usePage();
const success = computed(() => page.props.flash?.flash);

const form = useForm({
  name: '',
  email: '',
  subject: '',
  message: '',
});

const submit = () => {
  form.post(route('contact.send'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};
</script>

<template>
  <Head title="Contact" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap m-hero-inner">
        <span class="m-eyebrow">Contact</span>
        <h1 class="m-h1">Even sparren?<br>We helpen je graag</h1>
        <p class="m-lead">Een vraag, idee of gewoon hallo zeggen — stuur ons een bericht en we reageren binnen één werkdag.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap contact-grid">
        <div>
          <div v-if="success" class="contact-success">{{ success }}</div>
          <form class="m-card" @submit.prevent="submit" style="padding:28px;">
            <div class="form-row-2">
              <div class="field">
                <label>Naam</label>
                <input v-model="form.name" type="text" required />
                <div v-if="form.errors.name" class="err">{{ form.errors.name }}</div>
              </div>
              <div class="field">
                <label>E-mailadres</label>
                <input v-model="form.email" type="email" required />
                <div v-if="form.errors.email" class="err">{{ form.errors.email }}</div>
              </div>
            </div>
            <div class="field">
              <label>Onderwerp <span class="m-muted">(optioneel)</span></label>
              <input v-model="form.subject" type="text" />
            </div>
            <div class="field">
              <label>Bericht</label>
              <textarea v-model="form.message" rows="6" required></textarea>
              <div v-if="form.errors.message" class="err">{{ form.errors.message }}</div>
            </div>
            <button type="submit" class="m-btn m-btn-primary m-btn-lg" :disabled="form.processing">
              {{ form.processing ? 'Versturen…' : 'Verstuur bericht' }}
            </button>
          </form>
        </div>

        <aside class="contact-side">
          <div class="m-card">
            <h3 class="m-h3" style="margin-bottom:14px;">Direct contact</h3>
            <div class="contact-line">
              <span class="contact-ic">✉</span>
              <div><div class="m-muted" style="font-size:13px;">E-mail</div><a href="mailto:support@easyinvoice.nl" style="font-weight:600;color:var(--brand);">support@easyinvoice.nl</a></div>
            </div>
            <div class="contact-line">
              <span class="contact-ic">⏱</span>
              <div><div class="m-muted" style="font-size:13px;">Reactietijd</div><div style="font-weight:600;">Binnen 1 werkdag</div></div>
            </div>
            <div class="contact-line">
              <span class="contact-ic">📍</span>
              <div><div class="m-muted" style="font-size:13px;">Adres</div><div style="font-weight:600;">Amsterdam, Nederland</div></div>
            </div>
          </div>
          <div class="m-card" style="margin-top:16px;">
            <h3 class="m-h3" style="margin-bottom:8px;font-size:17px;">Liever zelf zoeken?</h3>
            <p class="m-muted" style="font-size:14px;">Veel antwoorden vind je direct in ons helpcentrum of de veelgestelde vragen.</p>
          </div>
        </aside>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.contact-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: var(--ink-2); }
.field input, .field textarea {
  width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: var(--r-sm);
  background: var(--surface); font-family: inherit; font-size: 15px; color: var(--ink); resize: vertical;
}
.field input:focus, .field textarea:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.err { color: var(--brand); font-size: 13px; margin-top: 4px; }
.contact-success {
  background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border);
  padding: 14px 16px; border-radius: var(--r); margin-bottom: 16px; font-weight: 600; font-size: 14px;
}
.contact-line { display: flex; gap: 12px; align-items: center; padding: 10px 0; border-top: 1px solid var(--border); }
.contact-line:first-of-type { border-top: none; }
.contact-ic {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  background: var(--brand-tint); display: inline-flex; align-items: center; justify-content: center; font-size: 16px;
}
@media (max-width: 760px) {
  .contact-grid { grid-template-columns: 1fr; }
  .form-row-2 { grid-template-columns: 1fr; }
}
</style>
