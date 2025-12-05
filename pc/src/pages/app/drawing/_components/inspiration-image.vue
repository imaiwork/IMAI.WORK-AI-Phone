<template>
    <popup
        ref="inspirationImageRef"
        width="476px"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        style="padding: 0; background-color: var(--app-bg-color-1)">
        <div class="py-[18px] -my-4">
            <div class="absolute top-[18px] right-[18px] w-6 h-6" @click="close">
                <close-btn></close-btn>
            </div>
            <div class="font-bold text-[20px] text-white px-[18px]">图片灵感库</div>
            <div class="mt-3">
                <ElTabs v-model="categoryVal" type="card">
                    <ElTabPane :name="item.id" :label="item.title" v-for="(item, index) in optionsData.assembleLists">
                        <div class="h-[26rem]">
                            <ElScrollbar>
                                <div class="grid grid-cols-3 gap-2 px-4 py-1">
                                    <div
                                        class="relative overflow-hidden cursor-pointer rounded-lg hover:"
                                        v-for="(value, sub_index) in item.subs"
                                        :key="sub_index"
                                        :class="[isNotIncluded(value) ? 'shadow-[0_0_0_1px_var(--color-primary)]' : '']"
                                        @click="handleAssembleItem(value)">
                                        <img :src="value.pic" class="w-full h-full object-cover" />
                                        <div class="absolute bottom-2 left-0 w-full flex justify-center">
                                            <div class="line-clamp-1 title-text">
                                                {{ value.title }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </ElScrollbar>
                        </div>
                    </ElTabPane>
                </ElTabs>
            </div>
            <div class="mt-5 px-4">
                <template v-if="optionsData.assembleLists.length > 0">
                    <div class="bg-app-bg-3 border border-app-border-2 rounded-lg px-4 py-2 min-h-[40px] relative mt-4">
                        <div class="text-white text-xs">
                            {{ `${getAssemblePrompt}` }}
                        </div>
                        <div class="absolute bottom-1.5 right-2 cursor-pointer" @click="assembleItemValue = []">
                            <Icon name="el-icon-Delete" color="#FF5733"></Icon>
                        </div>
                    </div>
                    <div class="flex justify-center mt-4">
                        <ElButton
                            class="w-[318px] !rounded-full !h-[50px]"
                            type="primary"
                            :disabled="assembleItemValue.length == 0"
                            @click="useAssemblePrompt()"
                            >使用此描述</ElButton
                        >
                    </div>
                </template>
                <ElEmpty v-else />
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getQuickComposeList } from "@/api/drawing";

const emit = defineEmits<{
    (event: "close"): void;
    (event: "use-assemble", prompt: string[]): void;
}>();

const inspirationImageRef = shallowRef();

const categoryVal = ref();

const assembleItemValue = ref<any[]>([]);

const { optionsData } = useDictOptions<{
    assembleLists: any[];
}>({
    assembleLists: {
        api: getQuickComposeList,
        transformData: (data) => {
            if (data.length) {
                categoryVal.value = data[0].id;
            }
            return data;
        },
    },
});

const getAssemblePrompt = computed(() => {
    return assembleItemValue.value.map((item) => item.title);
});

const isNotIncluded = (data: any) => {
    return assembleItemValue.value.some((item) => item.id == data.id);
};

const handleAssembleItem = (data: any) => {
    const isExist = assembleItemValue.value.some((item) => item.id == data.id);
    if (isExist) {
        assembleItemValue.value = assembleItemValue.value.filter((item) => item.id != data.id);
    } else {
        assembleItemValue.value.push(data);
    }
};

const useAssemblePrompt = () => {
    emit("use-assemble", getAssemblePrompt.value);
    close();
};

const open = () => {
    inspirationImageRef.value.open();
};

const close = () => {
    emit("close");
    inspirationImageRef.value.close();
};

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
:deep(.el-tabs) {
    .el-tabs__nav,
    .el-tabs__header {
        border-color: var(--app-border-color-2);
    }
    .el-tabs__item {
        color: #ffffff;
        border-color: var(--app-border-color-2);
    }
}
.title-text {
    @apply px-5 h-[34px] flex items-center justify-center rounded-full text-white;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.3) 0%, rgba(56, 56, 56, 0.3) 100%);
    box-shadow: 0px 6px 12px 0px rgba(0, 0, 0, 0.24);
    backdrop-filter: blur(6px);
}
</style>
