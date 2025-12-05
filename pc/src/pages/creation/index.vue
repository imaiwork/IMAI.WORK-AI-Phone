<template>
    <div class="flex flex-col h-full p-4">
        <div class="pb-0">
            <div class="text-2xl font-bold mb-2">创作中心</div>
            <div class="flex gap-4">
                <div
                    v-for="(tab, index) in sceneTabs"
                    :key="index"
                    class="bg-white rounded-[25px] h-[40px] flex items-center justify-center gap-2 px-3 cursor-pointer border-[2px] border-[transparent] hover:border-[#000000]"
                    :class="tab.value === sceneType ? '!border-[#000000]' : ''"
                    @click="handleSceneType(tab.value)">
                    <Icon :name="`local-icon-${tab.icon}`" :size="22" />
                    <div class="text-base font-bold">{{ tab.label }}</div>
                </div>
            </div>
        </div>
        <div class="grow min-h-0 mt-5">
            <CreationContent v-if="sceneType == 1" />
            <CreationImage v-if="sceneType == 2" />
        </div>
    </div>
</template>

<script setup lang="ts">
import CreationContent from "./_components/content.vue";
import CreationImage from "./_components/image.vue";

const route = useRoute();

const sceneTabs = ref([
    { label: "AI创作", value: 1, icon: "edit2" },
    { label: "AI设计", value: 2, icon: "pic" },
]);
const sceneType = ref<number>();

const updateSceneType = (type: number) => {
    sceneType.value = type;
    replaceState({ type: sceneType.value });
};

const handleSceneType = (type: number) => {
    updateSceneType(type);
};

const init = () => {
    const defaultType = 1;
    const queryType = Number(route.query.type);
    updateSceneType(queryType || defaultType);
};

onMounted(() => {
    init();
});
</script>

<style scoped lang="scss">
:deep() {
    .el-loading-mask {
        background: transparent;
    }
}
</style>
