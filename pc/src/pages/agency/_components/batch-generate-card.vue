<template>
    <popup
        ref="popupRef"
        width="520px"
        top="15vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Key" :size="20" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">批量生成卡密</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1"
                            >Batch Generate Cards</span
                        >
                    </div>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex flex-col p-6 gap-6">
                <div class="flex flex-col gap-2 text-left">
                    <label class="text-sm font-[1000] text-slate-800">选择生成套餐</label>
                    <ElSelect v-model="formData.package" placeholder="请选择" class="w-full" size="large">
                        <ElOption
                            v-for="item in optionsData.cardLists"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id" />
                    </ElSelect>
                </div>

                <div class="flex flex-col gap-2 text-left">
                    <label class="text-sm font-[1000] text-slate-800">生成卡密数量</label>
                    <ElInput
                        v-model="formData.quantity"
                        v-number-input="{ min: 1, max: 9999 }"
                        placeholder="请输入"
                        type="number"
                        size="large"
                        class="w-full">
                    </ElInput>
                </div>

                <div class="flex flex-col gap-2 text-left">
                    <label class="text-sm font-[1000] text-slate-800">每张卡密可使用的次数</label>
                    <ElInput
                        v-model="formData.use_times"
                        v-number-input="{ min: 1, max: 99 }"
                        placeholder="请输入"
                        type="number"
                        size="large"
                        class="w-full">
                    </ElInput>
                </div>
            </div>

            <div
                class="px-8 py-6 border-t border-slate-50 flex items-center justify-end shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.01)]">
                <div class="flex items-center gap-3">
                    <button
                        @click="close"
                        class="px-6 h-11 rounded-xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">
                        取消
                    </button>
                    <ElButton
                        type="primary"
                        :disabled="isLock"
                        :loading="isLock"
                        class="!px-10 !h-11 !rounded-xl !text-sm !font-[1000] hover:scale-[1.02] active:scale-95 transition-all"
                        @click="lockFn">
                        {{ isLock ? "生成中..." : "确认生成" }}
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getAgentCardPackageList, generateAgentCard } from "@/api/user";
import { useUserStore } from "@/stores/user";

const userStore = useUserStore();

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const popupRef = ref();

const formData = reactive<{
    package: string;
    quantity: number;
    use_times: number;
}>({
    package: "",
    quantity: 1,
    use_times: 1,
});

const { optionsData } = useDictOptions<{
    cardLists: any[];
}>({
    cardLists: {
        api: getAgentCardPackageList,
        params: {
            page_no: 1,
            page_size: 1000,
        },
        transformData: (data: any) => data.lists,
    },
});

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    popupRef.value?.close();
    emit("close");
};

const handleConfirm = async () => {
    if (!formData.package) {
        feedback.msgWarning("请选择生成套餐");
        return;
    }
    try {
        await generateAgentCard({
            package_id: formData.package,
            count: formData.quantity,
            card_num: formData.use_times,
        });
        feedback.msgSuccess("生成成功");
        userStore.getUser();
        emit("success");
        close();
    } catch (error) {
        feedback.msgError(error);
    }
};

const { lockFn, isLock } = useLockFn(handleConfirm);

defineExpose({
    open,
    close,
});
</script>
