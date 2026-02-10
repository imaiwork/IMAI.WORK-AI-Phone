<template>
    <div class="flex flex-col h-full overflow-hidden">
        <div class="px-8 pt-8 pb-4">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <h1 class="text-[28px] font-[900] text-[#1E293B] tracking-tight">创作中心</h1>
                    <p class="text-[13px] font-medium text-[#94A3B8] mt-1">释放 AI 潜能，开启无限创意空间</p>
                </div>
            </div>

            <div class="flex gap-3 bg-[#F1F5F9] p-1.5 rounded-[20px] w-fit">
                <div
                    v-for="tab in sceneTabs"
                    :key="tab.value"
                    class="tab-item"
                    :class="{ 'is-active': tab.value === sceneType }"
                    @click="handleSceneType(tab.value)">
                    <Icon :name="`local-icon-${tab.icon}`" :size="20" />
                    <span class="text-[14px] font-black">{{ tab.label }}</span>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 mt-2">
            <Transition name="fade-slide" mode="out-in">
                <div :key="sceneType" class="h-full">
                    <CreationContent v-if="sceneType === 1" />
                    <CreationImage v-if="sceneType === 2" />
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup lang="ts">
import CreationContent from "./_components/content.vue";
import CreationImage from "./_components/image.vue";

const route = useRoute();

const sceneTabs = [
    { label: "AI 创作", value: 1, icon: "edit2" },
    { label: "AI 设计", value: 2, icon: "pic" },
];

const sceneType = ref<number>(1);

const updateSceneType = (type: number) => {
    sceneType.value = type;
    // 假设 replaceState 是工具函数或全局方法
    if (typeof replaceState === "function") {
        replaceState({ type: sceneType.value });
    }
};

const handleSceneType = (type: number) => {
    if (sceneType.value === type) return;
    updateSceneType(type);
};

onMounted(() => {
    const defaultType = 1;
    const queryType = Number(route.query.type);
    updateSceneType(queryType || defaultType);
});
</script>

<style scoped lang="scss">
.tab-item {
    @apply flex items-center gap-2 px-6 py-2.5 rounded-[15px] cursor-pointer transition-all duration-300;
    @apply text-[#64748B] hover:text-[#1E293B];

    &.is-active {
        @apply bg-primary text-white  shadow-[#0065fb]/20;
    }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

:deep(.el-loading-mask) {
    background: transparent !important;
}
</style>
