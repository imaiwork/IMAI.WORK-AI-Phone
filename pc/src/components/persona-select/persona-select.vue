<template>
    <popup
        ref="popupRef"
        width="900px"
        top="8vh"
        style="padding: 0; overflow: hidden"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        :show-close="false"
        @close="close">
        <div class="flex flex-col rounded-2xl h-[780px] overflow-hidden">
            <div
                class="px-8 py-5 flex items-center justify-between shrink-0 border-b border-br-extra-light bg-white z-10">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                        <Icon name="el-icon-User" color="#0065fb" :size="20" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">请选择人设</span>
                </div>
                <div class="flex items-center gap-4">
                    <span v-if="limit > 1" class="text-sm text-slate-400 font-medium">
                        已选
                        <span class="text-primary font-bold mx-0.5">{{ chooseLists.length }}</span>
                        / {{ limit }}
                    </span>
                    <div
                        class="w-9 h-9 flex items-center justify-center cursor-pointer hover:bg-gray-100 rounded-full transition-colors"
                        @click="close">
                        <close-btn />
                    </div>
                </div>
            </div>

            <div class="flex flex-col flex-1 min-h-0 bg-[#f9f9f9]/50">
                <ElScrollbar class="flex-1 min-h-0">
                    <div class="px-6 pt-5 pb-4">
                        <div v-if="loading" class="flex items-center justify-center py-20">
                            <div class="ai-loading-icon !border-[#0065fb]/30 !border-t-primary"></div>
                        </div>

                        <div v-else class="grid grid-cols-4 gap-4">
                            <div
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="relative flex flex-col items-center justify-center w-full h-52 rounded-2xl border-2 transition-all overflow-hidden bg-white cursor-pointer select-none"
                                :class="[
                                    isChoose(item)
                                        ? 'border-primary shadow-[0_0_0_3px_rgba(0,101,251,0.12)]'
                                        : 'border-[transparent] shadow-[0_2px_10px_rgba(0,0,0,0.05)] hover:shadow-[0_4px_16px_rgba(0,0,0,0.1)]',
                                    !item.is_configured ? 'opacity-70' : '',
                                    isDisabled(item) ? 'cursor-not-allowed' : '',
                                ]"
                                @click.stop="handleSelect(item)">
                                <div
                                    v-if="isChoose(item)"
                                    class="absolute inset-0 bg-[#0065fb]/5 pointer-events-none z-10 rounded-2xl"></div>

                                <div
                                    class="absolute top-2.5 right-2.5 w-6 h-6 rounded-full flex items-center justify-center z-20 transition-all duration-200"
                                    :class="
                                        isChoose(item)
                                            ? 'bg-primary shadow-[0_2px_6px_rgba(0,101,251,0.4)]'
                                            : 'bg-[#000000]/20 border-2 border-[#ffffff]/80'
                                    ">
                                    <Icon v-if="isChoose(item)" name="el-icon-Check" color="#fff" :size="13" />
                                </div>

                                <div class="absolute top-2.5 left-2.5 z-20">
                                    <div
                                        v-if="item.is_configured"
                                        class="flex items-center gap-1 bg-[#ECFDF5] border border-[#A7F3D0] px-2 py-0.5 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] inline-block"></span>
                                        <span class="text-[10px] text-[#059669] font-medium">已配置</span>
                                    </div>
                                    <div
                                        v-else
                                        class="flex items-center gap-1 bg-[#F3F4F6] border border-[#E5E7EB] px-2 py-0.5 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#D1D5DB] inline-block"></span>
                                        <span class="text-[10px] text-[#9CA3AF] font-medium">未配置</span>
                                    </div>
                                </div>

                                <div
                                    class="w-16 h-16 rounded-full overflow-hidden border-2 border-white shadow-md mb-3">
                                    <el-image
                                        v-if="item.avatar_url"
                                        :src="item.avatar_url"
                                        fit="cover"
                                        class="w-full h-full" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-[#E8EDF2]">
                                        <Icon name="el-icon-UserFilled" color="#B0BEC5" :size="28" />
                                    </div>
                                </div>

                                <p class="text-sm font-bold text-gray-900 mb-1.5 w-full text-center truncate px-3">
                                    {{ item.persona_name }}
                                </p>

                                <span
                                    class="px-2.5 py-0.5 rounded-full border border-[#E5E7EB] bg-white text-[11px] text-slate-500">
                                    {{ PersonTypeMap[item.persona_type as keyof typeof PersonTypeMap] }}
                                </span>

                                <div
                                    v-if="!item.is_configured"
                                    class="absolute bottom-0 left-0 right-0 h-9 flex items-center justify-center bg-[#F9FAFB] border-t border-[#F0F0F0] rounded-b-2xl gap-1">
                                    <Icon name="el-icon-EditPen" color="#9CA3AF" :size="14" />
                                    <span class="text-[11px] text-[#9CA3AF]">
                                        {{ skipUnConfig ? "未配置不可选" : "去完善信息" }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!loading && dataLists.length === 0"
                            class="flex flex-col items-center justify-center py-20 gap-3">
                            <Icon name="el-icon-UserFilled" color="#D1D5DB" :size="48" />
                            <p class="text-sm text-slate-400 font-medium">暂无人设，请先去创建</p>
                        </div>
                    </div>
                </ElScrollbar>

                <div
                    class="bg-[#ffffff]/90 backdrop-blur-sm px-6 py-4 border-t border-br-extra-light flex items-center justify-between gap-4 shrink-0">
                    <div
                        v-if="limit > 1"
                        class="flex items-center gap-2 cursor-pointer select-none active:opacity-70 transition-opacity"
                        @click="toggleSelect">
                        <div
                            class="w-6 h-6 rounded-full flex items-center justify-center transition-colors"
                            :class="
                                chooseLists.length > 0 && chooseLists.length === selectableList.length
                                    ? 'bg-primary'
                                    : 'border-2 border-[#D1D5DB] bg-[#F9FAFB]'
                            ">
                            <Icon
                                v-if="chooseLists.length > 0 && chooseLists.length === selectableList.length"
                                name="el-icon-Check"
                                color="#fff"
                                :size="13" />
                        </div>
                        <span class="text-sm text-slate-700 font-medium">全选</span>
                    </div>

                    <div v-else class="flex-shrink-0"></div>

                    <ElButton
                        type="primary"
                        round
                        class="!h-11 !px-10 !text-sm !font-[1000] shadow-lg shadow-[#0065fb]/20 hover:scale-[1.01] active:scale-[0.99] transition-all"
                        @click="confirm">
                        确定选择{{ limit > 1 && chooseLists.length > 0 ? `（${chooseLists.length} 条）` : "" }}
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getPersonList } from "@/api/person";
import { PersonTypeMap } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        limit?: number;
        isConfig?: boolean;
        skipUnConfig?: boolean;
    }>(),
    {
        limit: 99,
        isConfig: true,
        skipUnConfig: false,
    }
);

const emit = defineEmits<{
    (e: "select", value: any | any[]): void;
    (e: "close"): void;
}>();

// ── Refs ──────────────────────────────────────────────
const popupRef = shallowRef();
const dataLists = ref<any[]>([]);
const chooseLists = ref<any[]>([]);
const loading = ref(false);

// ── 可选列表（跳过未配置时过滤） ──────────────────────
const selectableList = computed(() =>
    props.skipUnConfig ? dataLists.value.filter((item) => item.is_configured) : dataLists.value
);

// ── 数据加载 ──────────────────────────────────────────
const loadList = async () => {
    loading.value = true;
    try {
        const { lists } = await getPersonList({
            page_no: 1,
            page_size: 25000,
            is_configured: props.isConfig ? 1 : "",
        });
        dataLists.value = lists ?? [];
    } catch {
        feedback.msgError("加载人设列表失败");
        dataLists.value = [];
    } finally {
        loading.value = false;
    }
};

// ── 选择逻辑 ──────────────────────────────────────────
const isChoose = (item: any) => chooseLists.value.some((c) => c.id === item.id);

const isDisabled = (item: any) => props.skipUnConfig && !item.is_configured;

const handleSelect = (item: any) => {
    if (isDisabled(item)) {
        feedback.msgWarning("该人设未配置，无法选择");
        return;
    }
    const index = chooseLists.value.findIndex((c) => c.id === item.id);
    if (index > -1) {
        chooseLists.value.splice(index, 1);
    } else {
        if (props.limit === 1) {
            chooseLists.value = [item];
        } else {
            if (chooseLists.value.length >= props.limit) {
                feedback.msgWarning(`最多只能选择 ${props.limit} 个`);
            } else {
                chooseLists.value.push(item);
            }
        }
    }
};

const toggleSelect = () => {
    const selectable = selectableList.value;
    const allSelected = chooseLists.value.length === selectable.length && selectable.length > 0;

    if (allSelected) {
        chooseLists.value = [];
    } else {
        if (selectable.length > props.limit) {
            feedback.msgWarning(`最多只能选择 ${props.limit} 个`);
            chooseLists.value = selectable.slice(0, props.limit);
        } else {
            chooseLists.value = [...selectable];
        }
    }
};

const confirm = () => {
    if (chooseLists.value.length === 0) {
        feedback.msgWarning("请至少选择一个人设");
        return;
    }
    emit("select", props.limit === 1 ? chooseLists.value[0] : chooseLists.value);
    close();
};

// ── 弹窗控制 ──────────────────────────────────────────
const open = async () => {
    popupRef.value?.open();
    await nextTick();
    await loadList();
};

const close = () => {
    emit("close");
};

// ── 暴露 API ──────────────────────────────────────────
defineExpose({
    open,
    close,
    setChooseLists: (lists: any[]) => {
        chooseLists.value = JSON.parse(JSON.stringify(lists));
    },
});
</script>

<style scoped lang="scss">
.ai-loading-icon {
    width: 28px;
    height: 28px;
    border: 3px solid rgba(0, 101, 251, 0.2);
    border-top-color: #0065fb;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
