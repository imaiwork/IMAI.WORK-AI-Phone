<template>
    <ElButton v-if="!isStart" @click="handleStart" :disabled="disabled" :loading="loading" link>
        {{ isRetry ? endText : startText }}
    </ElButton>
    <VueCountdown v-else ref="vueCountdownRef" :time="seconds * 1000" v-slot="{ totalSeconds }" @end="handleEnd">
        {{ getChangeText(totalSeconds) }}
    </VueCountdown>
</template>
<script lang="ts">
import VueCountdown from "@chenfengyuan/vue-countdown";
import { useThrottleFn } from "@vueuse/core";
import { ElButton } from "element-plus";
export default defineComponent({
    components: {
        VueCountdown,
        ElButton,
    },
    props: {
        // 倒计时总秒数
        seconds: {
            type: Number,
            default: 60,
        },
        // 尚未开始时提示
        startText: {
            type: String,
            default: "发送验证码",
        },
        // 正在倒计时中的提示
        changeText: {
            type: String,
            default: "发送验证码（xs）",
        },
        // 倒计时结束时的提示
        endText: {
            type: String,
            default: "重新获取",
        },
        // 是否禁用
        disabled: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["click-get"],
    setup(props, { emit }) {
        const isStart = ref(false);
        const isRetry = ref(false);
        const start = async () => {
            isStart.value = true;
        };

        const getChangeText = (second) => {
            return props.changeText.replace("x", second);
        };
        const handleEnd = () => {
            isStart.value = false;
            isRetry.value = true;
        };
        const handleStart = useThrottleFn(
            () => {
                emit("click-get");
            },
            1000,
            false
        );
        return {
            getChangeText,
            isStart,
            start,
            isRetry,
            handleEnd,
            handleStart,
        };
    },
});
</script>
<style lang="scss" scoped></style>
