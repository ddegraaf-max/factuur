<script setup>
import { usePage } from '@inertiajs/vue3';
const version = usePage().props.version;
const brand = usePage().props.brand;
</script>

<template>
  <div class="auth-shell">
    <div class="auth-hero">
      <!-- Let op: gewone <a>, geen Inertia <Link>. De homepage is een Blade-pagina
           (geen Inertia-response); met <Link> toont Inertia een debug-modal i.p.v. te navigeren. -->
      <a href="/" class="auth-logo">
        <img :src="brand.icon" class="logo-mark" :alt="brand.name" />
        <span>{{ brand.name }}</span>
      </a>
      <div class="auth-tagline">
        <slot name="hero">
          <h2>{{ brand.auth_title }}</h2>
          <p>{{ brand.auth_subtitle }}</p>
        </slot>
      </div>
      <div class="auth-footer-text">© {{ new Date().getFullYear() }} {{ brand.name }} · {{ version }}</div>
    </div>
    <div class="auth-form-pane">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.auth-shell {
  min-height: 100vh;
  display: grid;
  /* minmax(0, ...) zodat de kolommen mogen krimpen; met kale 1fr krijgen ze de
     min-content-breedte van het formulier en scrolt de pagina zijwaarts. */
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  background: var(--bg);
}
.auth-hero, .auth-form-pane { min-width: 0; }
.auth-hero {
  background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
  color: white;
  padding: 60px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}
.auth-hero::before {
  content: '';
  position: absolute;
  top: -100px; right: -100px;
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
  border-radius: 50%;
}
.auth-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 22px;
  color: white;
  text-decoration: none;
  z-index: 1;
}
.auth-logo .logo-mark {
  width: 34px; height: 34px;
  object-fit: contain;
  display: block;
}
.auth-tagline { z-index: 1; }
.auth-tagline h2 {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 32px;
  letter-spacing: -0.02em;
  line-height: 1.15;
  margin-bottom: 14px;
}
.auth-tagline p {
  font-size: 16px;
  line-height: 1.6;
  opacity: 0.9;
  max-width: 380px;
  margin-bottom: 28px;
}
.auth-footer-text {
  font-size: 12px;
  opacity: 0.7;
  z-index: 1;
}
.auth-form-pane {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}
@media (max-width: 880px) {
  .auth-shell { grid-template-columns: minmax(0, 1fr); }
  .auth-hero { padding: 40px 30px; min-height: 260px; }
}
@media (max-width: 560px) {
  .auth-hero { padding: 28px 18px; min-height: 0; }
  .auth-form-pane { padding: 24px 14px 40px; align-items: flex-start; }
  .auth-tagline h2 { font-size: 26px; }
}
</style>
