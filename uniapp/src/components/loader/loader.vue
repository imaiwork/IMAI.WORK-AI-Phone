<template>
    <div>
        <div class="loader" :style="{ width: props.width, height: props.height }"></div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps({
    width: {
        type: String,
        default: "48px",
    },
    height: {
        type: String,
        default: "48px",
    },
});
</script>

<style scoped lang="scss">
.loader {
    margin: 0 auto;
    position: relative;
}

.loader:before {
    content: "";
    width: 48px;
    height: 5px;
    background: linear-gradient(0deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0) 100%),
        linear-gradient(0deg, #937dfe 0%, #0065fb 100%);
    position: absolute;
    top: 60px;
    left: 0;
    border-radius: 50%;
    animation: shadow 0.5s linear infinite;
}

.loader:after {
    content: "";
    width: 100%;
    height: 100%;
    background: linear-gradient(0deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0) 100%),
        linear-gradient(0deg, #937dfe 0%, #0065fb 100%);
    position: absolute;
    top: 0;
    left: 0;
    border-radius: 4px;
    animation: jump 0.5s linear infinite;
}

@keyframes jump {
    15% {
        border-bottom-right-radius: 3px;
    }

    25% {
        transform: translateY(9px) rotate(22.5deg);
    }

    50% {
        transform: translateY(18px) scale(1, 0.9) rotate(45deg);
        border-bottom-right-radius: 40px;
    }

    75% {
        transform: translateY(9px) rotate(67.5deg);
    }

    100% {
        transform: translateY(0) rotate(90deg);
    }
}

@keyframes shadow {
    0%,
    100% {
        transform: scale(1, 1);
    }

    50% {
        transform: scale(1.2, 1);
    }
}
</style>
