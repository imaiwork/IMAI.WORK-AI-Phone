<template>
    <view class="w-full h-full bg-[rgba(0,0,0,0.5)]" :style="{ paddingTop: paddingTop + 'px' }" @click="handleClick">
        <image :src="stepList[step - 1].src" class="w-full" mode="widthFix"></image>
    </view>
</template>

<script setup lang="ts">
import Step1 from "@/ai_modules/interview/static/images/common/interview_step1.png";
import Step2 from "@/ai_modules/interview/static/images/common/interview_step2.png";
import Step3 from "@/ai_modules/interview/static/images/common/interview_step3.png";
import Step4 from "@/ai_modules/interview/static/images/common/interview_step4.png";

const queryParams = ref<any>({});

const { safeArea } = uni.$u.sys();

const step = ref(1);
const stepList = ref<any[]>([
    {
        src: Step1,
    },
    {
        src: Step2,
    },
    {
        src: Step3,
    },
    {
        src: Step4,
    },
]);

const paddingTop = computed(() => {
    // #ifdef MP
    return safeArea.top + 48;
    // #endif
    // #ifndef MP
    return safeArea.top;
    // #endif
});

const handleClick = () => {
    if (step.value < stepList.value.length) {
        step.value++;
    } else {
        uni.$u.route({
            url: "/ai_modules/interview/pages/chat/chat",
            params: {
                record_id: queryParams.value.record_id,
                type: queryParams.value.type,
                id: queryParams.value.id,
            },
            type: "redirect",
        });
    }
};

onLoad((options: any) => {
    queryParams.value = options;
});
</script>

<style scoped></style>
