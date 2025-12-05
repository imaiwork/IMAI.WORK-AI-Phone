<template>
    <popup
        ref="popupRef"
        width="528px"
        class="choose-anchor-popup"
        style="
            padding: 0;
            background-color: var(--app-bg-color-2);
            box-shadow: 0px 0px 0px 1px var(--app-border-color-2);
        "
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        @close="close">
        <div class="rounded-xl overflow-hidden flex flex-col -my-2">
            <div class="flex items-center justify-between h-[50px] px-4">
                <div class="flex items-center gap-x-2">
                    <div class="w-6 h-6 flex items-center justify-center rounded-md border border-[#ffffff1a]">
                        <Icon name="local-icon-windows" :size="14"></Icon>
                    </div>
                    <div class="text-[20px] text-white font-bold">从已有音色中选择</div>
                </div>
                <div class="w-6 h-6" @click="close">
                    <close-btn />
                </div>
            </div>
            <div class="px-4 my-4">
                <div class="flex items-center rounded-full h-[50px] border border-[#2a2a2a] px-[5px]">
                    <ElInput
                        v-model="queryParams.name"
                        class="flex-1 search-input"
                        clearable
                        prefix-icon="el-icon-Search"
                        placeholder="请输入音色名称"
                        input-style="color: #ffffff"
                        @clear="search()"
                        @keyup.enter="search()"></ElInput>
                    <ElButton type="primary" class="!text-white !rounded-full !w-[116px] !h-10" @click="search()">
                        搜索
                    </ElButton>
                </div>
            </div>
            <div class="h-[500px] flex flex-col">
                <div class="grow min-h-0 cursor-pointer">
                    <ElTable :data="pager.lists" height="100%" v-loading="pager.loading" @row-click="choose">
                        <ElTableColumn label="音色名称" prop="name"> </ElTableColumn>
                        <ElTableColumn label="音色类型">
                            <template #default="{ row }">
                                <div v-if="row.voice_id == -1">
                                    <span>当前视频</span>
                                    <ElTag class="ml-3" effect="dark" type="primary" round :disable-transitions="true"
                                        >原生</ElTag
                                    >
                                </div>
                                <div v-else>
                                    <span>{{ row.builtin == 0 ? "内置音色" : "用户音色" }}</span>
                                    <ElTag
                                        class="ml-3 !border-none !text-white"
                                        color="#3BB840"
                                        round
                                        :disable-transitions="true"
                                        >免费</ElTag
                                    >
                                </div>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="操作">
                            <template #default="{ row }">
                                <div class="w-5 h-5 flex items-center justify-center rounded-full mx-auto">
                                    <Icon
                                        name="local-icon-success_fill"
                                        :size="20"
                                        :color="isChoose(row.voice_id) ? 'var(--color-primary)' : '#ffffff1a'"></Icon>
                                </div>
                            </template>
                        </ElTableColumn>
                    </ElTable>
                </div>
                <div class="flex justify-center my-2 px-2">
                    <pagination v-model="pager" layout="prev, pager, next" @change="changePage"></pagination>
                </div>
            </div>
            <div class="flex justify-center my-2 px-2" v-if="multiple">
                <ElButton type="primary" class="!rounded-full w-[318px] !h-[50px]" @click="handleConfirm()">
                    确定
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { DigitalHumanModelVersionEnum, ToneTypeEnum } from "@/pages/app/digital_human/_enums";
import { getVoiceList } from "@/api/digital_human";
import Popup from "@/components/popup/index.vue";
import { useAppStore } from "@/stores/app";
import cloneDeep from "lodash/cloneDeep";
const props = withDefaults(
    defineProps<{
        // 是否显示原声音
        is_show_original?: boolean;
        // 是否可以多选
        multiple?: boolean;
    }>(),
    {
        is_show_original: true,
        multiple: false,
    }
);

const emit = defineEmits(["close", "confirm"]);

const { is_show_original, multiple } = toRefs(props);

const popupRef = ref<InstanceType<typeof Popup>>();

const appStore = useAppStore();

const systemToneLists = computed(() => {
    const list = cloneDeep(
        appStore.getDigitalHumanConfig?.voice
            .filter((item: any) => item.status == "1")
            .map((item) => ({ ...item, voice_id: item.code, builtin: 0 }))
    );
    if (is_show_original.value) {
        list.unshift({ voice_id: -1, name: "视频原音", builtin: 1 });
    }
    return list;
});

const queryParams = reactive<any>({
    name: "",
    status: 1,
    model_version: "",
    builtin: ToneTypeEnum.USER,
});

const { pager, getLists } = usePaging({
    fetchFun: getVoiceList,
    params: queryParams,
});

const search = async () => {
    const list = systemToneLists.value.filter((item) => item.name.includes(queryParams.name));
    await getLists();
    pager.lists.unshift(...list);
};

const selectTone = ref<any>();

const isChoose = (voice_id: number) => {
    if (multiple.value) {
        return selectTone.value?.findIndex((item: any) => item.voice_id === voice_id) > -1;
    }
    const { voice_id: currVoiceId } = selectTone.value || {};
    if (!currVoiceId) return false;
    return currVoiceId === voice_id;
};

const choose = (item: any) => {
    const { voice_id } = item;
    if (isChoose(voice_id)) {
        if (multiple.value) {
            selectTone.value = selectTone.value.filter((item: any) => item.voice_id !== voice_id);
        } else {
            selectTone.value = {};
        }
    } else {
        if (multiple.value) {
            selectTone.value.push(item);
        } else {
            selectTone.value = item;
        }
    }
    if (!multiple.value) {
        handleConfirm();
    }
};

const setChooseTone = (item: any) => {
    selectTone.value = item;
};

const changePage = async () => {
    await getLists();
    if (pager.page == 1) {
        pager.lists.unshift(...systemToneLists.value);
    }
};

const handleConfirm = () => {
    emit("confirm", selectTone.value);
    close();
};

const open = async (model_version: DigitalHumanModelVersionEnum) => {
    popupRef.value?.open();
    queryParams.model_version = model_version;
    await getLists();
    pager.lists.unshift(...systemToneLists.value);
};

const close = () => {
    emit("close");
};

watch(
    () => multiple.value,
    (val) => {
        if (val) {
            selectTone.value = [];
        } else {
            selectTone.value = {};
        }
    },
    {
        immediate: true,
    }
);
defineExpose({
    open,
    setChooseTone,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
:deep(.search-input) {
    .el-input__wrapper {
        background-color: transparent;
        box-shadow: none;
        &::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }
    }
}

.choose-anchor-popup {
    :deep() {
        .el-dialog__header,
        .el-dialog__footer {
            display: none;
            padding: 0;
        }
    }
}
</style>
