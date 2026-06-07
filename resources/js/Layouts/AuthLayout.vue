<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="auth-shell">
    <div class="auth-hero">
      <Link href="/" class="auth-logo">
        <span class="logo-mark">E</span>
        <span>EasyInvoice</span>
      </Link>
      <div class="auth-tagline">
        <slot name="hero">
          <h2>Facturen maken zonder gedoe</h2>
          <p>Nederlandse facturatie voor MKB en ZZP'ers.</p>
        </slot>
      </div>
      <div class="auth-footer-text">© 2026 EasyInvoice</div>
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
  grid-template-columns: 1fr 1fr;
  background: var(--bg);
}
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
  width: 32px; height: 32px;
  background: white;
  color: var(--brand);
  border-radius: 7px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: system-ui, sans-serif;
  font-weight: 700;
  font-size: 18px;
  line-height: 1;
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
  .auth-shell { grid-template-columns: 1fr; }
  .auth-hero { padding: 40px 30px; min-height: 260px; }
}
</style>
