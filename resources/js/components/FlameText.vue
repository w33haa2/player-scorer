<script setup lang="ts">
import { useId } from 'vue';

/**
 * Text that burns: flickering flames rise from the letters (styles are
 * `.flame-text` in app.css). Each instance carries its own tiny SVG filter
 * that bends the flames into tongues; stepping its turbulence seed makes
 * them flicker like an animated GIF.
 */
defineProps<{
    text: string;
}>();

const filterId = `flame-${useId()}`;

// SVG animations ignore the CSS reduced-motion rule, so gate this one here.
const animateFlicker =
    typeof window === 'undefined' ||
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
</script>

<template>
    <span class="flame-text"
        ><span
            class="flame-text__fire"
            :style="{ filter: `url(#${filterId})` }"
            aria-hidden="true"
            >{{ text }}</span
        ><span class="flame-text__letters">{{ text }}</span
        ><svg
            class="flame-text__defs"
            width="0"
            height="0"
            aria-hidden="true"
            focusable="false"
        >
            <filter
                :id="filterId"
                x="-10%"
                y="-30%"
                width="120%"
                height="160%"
                color-interpolation-filters="sRGB"
            >
                <feTurbulence
                    type="fractalNoise"
                    baseFrequency="0.035 0.13"
                    numOctaves="2"
                    seed="1"
                    result="noise"
                >
                    <animate
                        v-if="animateFlicker"
                        attributeName="seed"
                        values="1;2;3;4;5;6;7;8"
                        dur="0.96s"
                        calcMode="discrete"
                        repeatCount="indefinite"
                    />
                </feTurbulence>
                <feDisplacementMap
                    in="SourceGraphic"
                    in2="noise"
                    scale="7"
                    xChannelSelector="R"
                    yChannelSelector="G"
                />
            </filter></svg
    ></span>
</template>
