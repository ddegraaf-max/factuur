<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  users: Array,
  invitations: Array,
  roles: Object, // { owner: 'Beheerder', ... }
});

const roleDescriptions = {
  owner: 'Volledige toegang: ook instellingen, rapporten, abonnement en teambeheer.',
  staff: 'Dagelijks werk: offertes, facturen, klanten, producten en inkoop. Geen instellingen, rapporten of abonnement.',
  accountant: 'Mag alles inzien en rapporten/exports gebruiken (o.a. BTW-overzicht), maar niets aanmaken of wijzigen.',
};

const inviteForm = useForm({
  email: '',
  role: 'staff',
});

const invite = () => {
  inviteForm.post(route('settings.team.invite'), {
    preserveScroll: true,
    onSuccess: () => inviteForm.reset(),
  });
};

const initials = (name) => {
  const parts = (name || '').trim().split(/\s+/);
  return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
};

const changeRole = (user, event) => {
  const role = event.target.value;
  router.patch(route('settings.team.role', user.id), { role }, {
    preserveScroll: true,
    onError: () => router.reload({ only: ['users'] }),
  });
};

const removeUser = (user) => {
  if (confirm(`${user.name} verwijderen uit het team? Diegene kan daarna niet meer inloggen.`)) {
    router.delete(route('settings.team.remove', user.id), { preserveScroll: true });
  }
};

const resend = (inv) => {
  router.post(route('settings.team.invite.resend', inv.id), {}, { preserveScroll: true });
};

const revoke = (inv) => {
  if (confirm(`Uitnodiging voor ${inv.email} intrekken?`)) {
    router.delete(route('settings.team.invite.revoke', inv.id), { preserveScroll: true });
  }
};
</script>

<template>
  <Head title="Team" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">Instellingen / <span class="breadcrumb-current">Team</span></div>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Team</h1>
        <p class="page-subtitle">Nodig collega's of je boekhouder uit en bepaal per persoon wat diegene mag — zonder extra kosten.</p>
      </div>
    </div>

    <div class="single-col">
      <!-- Uitnodigen -->
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">Iemand uitnodigen</div>
            <div class="card-subtitle">Diegene ontvangt een e-mail met een beveiligde link (7 dagen geldig) en kiest zelf een wachtwoord.</div>
          </div>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label>E-mailadres *</label>
            <input type="email" v-model="inviteForm.email" placeholder="collega@bedrijf.nl" maxlength="180">
            <div v-if="inviteForm.errors.email" class="field-error">{{ inviteForm.errors.email }}</div>
          </div>

          <label style="display:block;font-size:13px;font-weight:500;color:var(--text-2);margin-bottom:8px;">Rol</label>
          <div class="role-options">
            <label v-for="(label, key) in roles" :key="key" class="role-opt" :class="{ on: inviteForm.role === key }">
              <input type="radio" :value="key" v-model="inviteForm.role">
              <div>
                <div class="role-opt-title">{{ label }}</div>
                <div class="role-opt-sub">{{ roleDescriptions[key] }}</div>
              </div>
            </label>
          </div>
          <div v-if="inviteForm.errors.role" class="field-error">{{ inviteForm.errors.role }}</div>

          <button class="btn btn-primary" style="margin-top:14px;" :disabled="inviteForm.processing" @click="invite">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            {{ inviteForm.processing ? 'Versturen…' : 'Uitnodiging versturen' }}
          </button>
        </div>
      </div>

      <!-- Openstaande uitnodigingen -->
      <div v-if="invitations.length > 0" class="card" style="margin-top:16px;">
        <div class="card-header"><div class="card-title">Openstaande uitnodigingen</div></div>
        <div class="card-body" style="padding-top:6px;">
          <div v-for="inv in invitations" :key="inv.id" class="team-row">
            <span class="team-avatar pending">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>
            </span>
            <div class="team-info">
              <div class="team-name">{{ inv.email }}</div>
              <div class="team-meta">
                {{ roles[inv.role] || inv.role }} ·
                <span v-if="inv.expired" style="color:var(--brand);font-weight:600;">verlopen</span>
                <span v-else>geldig tot {{ inv.expires_label }}</span>
              </div>
            </div>
            <button class="btn btn-secondary btn-sm" @click="resend(inv)">Opnieuw versturen</button>
            <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="revoke(inv)">Intrekken</button>
          </div>
        </div>
      </div>

      <!-- Teamleden -->
      <div class="card" style="margin-top:16px;">
        <div class="card-header">
          <div>
            <div class="card-title">Teamleden</div>
            <div class="card-subtitle">{{ users.length }} {{ users.length === 1 ? 'persoon heeft' : 'personen hebben' }} toegang tot deze omgeving.</div>
          </div>
        </div>
        <div class="card-body" style="padding-top:6px;">
          <div v-for="u in users" :key="u.id" class="team-row">
            <span class="team-avatar">{{ initials(u.name) }}</span>
            <div class="team-info">
              <div class="team-name">
                {{ u.name }}
                <span v-if="u.is_self" class="team-self">jij</span>
                <span v-if="u.two_factor" class="team-2fa" title="Tweestapsverificatie ingeschakeld">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  2FA
                </span>
              </div>
              <div class="team-meta">{{ u.email }} · sinds {{ u.joined_label }}</div>
            </div>
            <select
              class="team-role-select"
              :value="u.role"
              :disabled="u.is_self"
              :title="u.is_self ? 'Je kunt je eigen rol niet aanpassen' : 'Rol aanpassen'"
              @change="changeRole(u, $event)"
            >
              <option v-for="(label, key) in roles" :key="key" :value="key">{{ label }}</option>
            </select>
            <button v-if="!u.is_self" class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="removeUser(u)">Verwijder</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.role-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.role-opt {
  display: flex; gap: 10px; align-items: flex-start;
  border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px;
  cursor: pointer; transition: border-color .15s, background .15s;
}
.role-opt:hover { background: var(--surface-2); }
.role-opt.on { border-color: var(--brand); background: var(--brand-tint); }
.role-opt input { margin-top: 3px; width: 15px; height: 15px; accent-color: var(--brand); flex: none; }
.role-opt-title { font-weight: 600; font-size: 13.5px; }
.role-opt-sub { font-size: 12px; color: var(--text-3); margin-top: 3px; line-height: 1.5; }

.team-row {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 0; border-bottom: 1px solid var(--border);
}
.team-row:last-child { border-bottom: none; }
.team-avatar {
  width: 38px; height: 38px; border-radius: 100px; flex: none;
  background: var(--brand-tint); color: var(--brand-darker);
  display: inline-flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px;
}
.team-avatar.pending { background: var(--surface-2); color: var(--text-3); }
.team-info { flex: 1; min-width: 0; }
.team-name { font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.team-self {
  font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  background: var(--surface-3); color: var(--text-2); border-radius: 100px; padding: 2px 8px;
}
.team-2fa {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10.5px; font-weight: 700;
  background: var(--success-bg); color: var(--success);
  border: 1px solid var(--success-border); border-radius: 100px; padding: 2px 8px;
}
.team-meta { font-size: 12.5px; color: var(--text-3); margin-top: 2px; overflow-wrap: anywhere; }
.team-role-select { width: 210px; flex: none; }

@media (max-width: 760px) {
  .role-options { grid-template-columns: minmax(0, 1fr); }
  .team-row { flex-wrap: wrap; }
  .team-role-select { width: 100%; }
}
</style>
