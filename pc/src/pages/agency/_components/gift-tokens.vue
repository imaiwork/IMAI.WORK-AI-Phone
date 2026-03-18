<template>
    <popup
        ref="popupRef"
        width="480px"
        top="20vh"
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
                        <Icon name="el-icon-Present" :size="20" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">赠送算力</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.1em] mt-1"
                            >Gift Tokens</span
                        >
                    </div>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex flex-col p-6 gap-6">
                <div class="flex flex-col gap-3 text-left">
                    <label class="text-sm font-[1000] text-slate-800">赠送给</label>
                    <div class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="relative shrink-0">
                            <div
                                class="absolute -inset-1 bg-gradient-to-tr from-emerald-400 to-teal-300 rounded-full opacity-20"></div>
                            <img
                                :src="targetUser.avatar"
                                class="relative w-12 h-12 rounded-full border-2 border-white object-cover bg-white"
                                alt="avatar" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-[1000] text-slate-800 text-sm truncate">{{
                                targetUser.name || "--"
                            }}</span>
                            <span class="text-slate-400 text-xs font-bold mt-0.5 tracking-wider font-mono">{{
                                targetUser.phone || "--"
                            }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 text-left">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-[1000] text-slate-800">赠送数量</label>
                        <div class="flex items-center text-[11px] font-bold text-slate-400 gap-2">
                            当前可用:
                            <span
                                ><ElStatistic
                                    :value="userTokens"
                                    :precision="2"
                                    :value-style="{
                                        color: 'var(--color-primary)',
                                        fontSize: '11px',
                                        fontWeight: 'bold',
                                        letterSpacing: '0.1em',
                                    }"
                            /></span>
                            点算力
                        </div>
                    </div>
                    <ElInput
                        v-model="formData.amount"
                        v-number-input="{ min: 1, max: userTokens }"
                        placeholder="请输入赠送数量"
                        type="number"
                        class="w-full">
                        <template #append>
                            <span class="font-bold text-slate-500">点</span>
                        </template>
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
                        {{ isLock ? "赠送中..." : "确认赠送" }}
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { agentGiftTokens } from "@/api/user";
import { useUserStore } from "@/stores/user";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const popupRef = ref();

const targetUser = reactive({
    user_id: "",
    name: "",
    phone: "",
    avatar: "",
});

const formData = reactive({
    amount: 1,
});

const open = (row: any) => {
    if (row) {
        targetUser.user_id = row.user_id;
        targetUser.name = row.nickname;
        targetUser.phone = row.mobile;
        targetUser.avatar = row.avatar;
    }

    popupRef.value?.open();
};

const close = () => {
    popupRef.value?.close();
    emit("close");
};

const handleConfirm = async () => {
    try {
        await agentGiftTokens({
            user_id: targetUser.user_id,
            tokens: Number(formData.amount),
        });
        userStore.getUser();
        feedback.msgSuccess("赠送成功");
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
