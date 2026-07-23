<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        /** Render as a fixed, full-viewport background instead of filling the parent. */
        fullscreen?: boolean;
    }>(),
    { fullscreen: false },
);

type ParticleNode = {
    /** Base (drifting) position. */
    x: number;
    y: number;
    /** Drift velocity. */
    vx: number;
    vy: number;
    /** Transient render offset caused by the cursor (springs back to 0). */
    ox: number;
    oy: number;
    /** Cached render position for the current frame. */
    px: number;
    py: number;
};

const canvas = ref<HTMLCanvasElement | null>(null);

let ctx: CanvasRenderingContext2D | null = null;
let frame = 0;
let width = 0;
let height = 0;
let nodes: ParticleNode[] = [];

const mouse = { x: -9999, y: -9999, active: false };

const LINK_DISTANCE = 140;
const MOUSE_DISTANCE = 190;
const PUSH_STRENGTH = 28;

function lineColor(alpha: number): string {
    return `rgba(129, 140, 248, ${alpha})`;
}

function initNodes(): void {
    const density = Math.round((width * height) / 13000);
    const count = Math.max(28, Math.min(90, density));

    nodes = Array.from({ length: count }, () => {
        const x = Math.random() * width;
        const y = Math.random() * height;
        const angle = Math.random() * Math.PI * 2;
        const speed = 0.15 + Math.random() * 0.35;

        return {
            x,
            y,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            ox: 0,
            oy: 0,
            px: x,
            py: y,
        };
    });
}

function resize(): void {
    if (!canvas.value || !ctx) {
        return;
    }

    const dpr = Math.min(window.devicePixelRatio || 1, 2);

    if (props.fullscreen) {
        width = window.innerWidth;
        height = window.innerHeight;
    } else {
        const parent = canvas.value.parentElement;

        if (!parent) {
            return;
        }

        const rect = parent.getBoundingClientRect();
        width = rect.width;
        height = rect.height;
    }

    canvas.value.width = width * dpr;
    canvas.value.height = height * dpr;
    canvas.value.style.width = `${width}px`;
    canvas.value.style.height = `${height}px`;

    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    initNodes();
}

function updateNode(node: ParticleNode): void {
    // Drift the base position and softly wrap/bounce at the edges.
    node.x += node.vx;
    node.y += node.vy;

    if (node.x <= 0 || node.x >= width) {
        node.vx *= -1;
        node.x = Math.max(0, Math.min(width, node.x));
    }

    if (node.y <= 0 || node.y >= height) {
        node.vy *= -1;
        node.y = Math.max(0, Math.min(height, node.y));
    }

    // Cursor repulsion is a transient offset recomputed from the base each
    // frame, so it can never accumulate into a permanent cluster.
    let targetX = 0;
    let targetY = 0;

    if (mouse.active) {
        const dx = node.x - mouse.x;
        const dy = node.y - mouse.y;
        const distance = Math.hypot(dx, dy);

        if (distance > 0.001 && distance < MOUSE_DISTANCE) {
            const force = (1 - distance / MOUSE_DISTANCE) * PUSH_STRENGTH;
            targetX = (dx / distance) * force;
            targetY = (dy / distance) * force;
        }
    }

    // Ease toward the target offset for fluid motion.
    node.ox += (targetX - node.ox) * 0.12;
    node.oy += (targetY - node.oy) * 0.12;

    node.px = node.x + node.ox;
    node.py = node.y + node.oy;
}

function render(): void {
    if (!ctx) {
        return;
    }

    ctx.clearRect(0, 0, width, height);

    for (const node of nodes) {
        updateNode(node);

        ctx.beginPath();
        ctx.arc(node.px, node.py, 2, 0, Math.PI * 2);
        ctx.fillStyle = lineColor(0.55);
        ctx.fill();
    }

    for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
            const dx = nodes[i].px - nodes[j].px;
            const dy = nodes[i].py - nodes[j].py;
            const distance = Math.hypot(dx, dy);

            if (distance < LINK_DISTANCE) {
                ctx.beginPath();
                ctx.moveTo(nodes[i].px, nodes[i].py);
                ctx.lineTo(nodes[j].px, nodes[j].py);
                ctx.strokeStyle = lineColor(
                    0.14 * (1 - distance / LINK_DISTANCE),
                );
                ctx.lineWidth = 1;
                ctx.stroke();
            }
        }
    }

    if (mouse.active) {
        for (const node of nodes) {
            const dx = node.px - mouse.x;
            const dy = node.py - mouse.y;
            const distance = Math.hypot(dx, dy);

            if (distance < MOUSE_DISTANCE) {
                ctx.beginPath();
                ctx.moveTo(node.px, node.py);
                ctx.lineTo(mouse.x, mouse.y);
                ctx.strokeStyle = lineColor(
                    0.35 * (1 - distance / MOUSE_DISTANCE),
                );
                ctx.lineWidth = 1;
                ctx.stroke();
            }
        }

        ctx.beginPath();
        ctx.arc(mouse.x, mouse.y, 3, 0, Math.PI * 2);
        ctx.fillStyle = lineColor(0.85);
        ctx.fill();
    }

    frame = requestAnimationFrame(render);
}

function onPointerMove(event: MouseEvent): void {
    if (!canvas.value) {
        return;
    }

    const rect = canvas.value.getBoundingClientRect();

    mouse.x = event.clientX - rect.left;
    mouse.y = event.clientY - rect.top;
    mouse.active =
        mouse.x >= 0 && mouse.y >= 0 && mouse.x <= width && mouse.y <= height;
}

function onPointerLeave(): void {
    mouse.active = false;
}

onMounted(() => {
    if (!canvas.value) {
        return;
    }

    ctx = canvas.value.getContext('2d');

    if (!ctx) {
        return;
    }

    resize();

    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('mouseout', onPointerLeave);

    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (prefersReducedMotion) {
        render();
        cancelAnimationFrame(frame);

        return;
    }

    frame = requestAnimationFrame(render);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(frame);
    window.removeEventListener('resize', resize);
    window.removeEventListener('mousemove', onPointerMove);
    window.removeEventListener('mouseout', onPointerLeave);
});
</script>

<template>
    <canvas
        ref="canvas"
        class="pointer-events-none h-full w-full"
        :class="fullscreen ? 'fixed inset-0 z-0' : 'absolute inset-0'"
        aria-hidden="true"
    />
</template>
