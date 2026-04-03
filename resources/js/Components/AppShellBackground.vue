<script setup>
/** Zelfde atmosferische achtergrond als het inlogscherm: gradient + merk-gloeden. */
</script>

<template>
    <div
        class="relative min-h-screen overflow-hidden bg-gradient-to-br from-slate-950 via-[#0c2847] to-slate-900"
    >
        <div class="pointer-events-none fixed inset-0" aria-hidden="true">
            <div
                class="absolute -top-32 right-[-10%] h-[28rem] w-[28rem] rounded-full bg-brand-blue/20 blur-3xl"
            />
            <div
                class="absolute bottom-[-15%] left-[-5%] h-[22rem] w-[22rem] rounded-full bg-brand-red/18 blur-3xl"
            />
            <div
                class="absolute top-[40%] left-[20%] h-64 w-64 rounded-full bg-brand-green/12 blur-3xl"
            />
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(0,104,183,0.12),transparent)]"
            />
        </div>
        <div class="relative z-10 min-h-screen">
            <slot />
        </div>
    </div>
</template>
