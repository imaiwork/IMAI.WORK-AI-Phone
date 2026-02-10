<template>
    <ElDrawer v-model="show" size="50%" :with-header="false" body-class="!p-0">
        <div class="h-full w-full flex flex-col bg-white">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                        <Icon name="el-icon-Document" color="#0065fb" :size="18" />
                    </div>
                    <div>
                        <span class="text-gray-950 text-base font-[1000] tracking-tight">内容草稿箱</span>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Draft Editor</p>
                    </div>
                </div>
                <div
                    class="w-9 h-9 flex items-center justify-center cursor-pointer hover:bg-slate-50 rounded-full transition-all"
                    @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="flex-1 p-6 overflow-hidden">
                <div class="h-full w-full relative group">
                    <textarea
                        v-model="editContent"
                        class="draft-textarea"
                        placeholder="在此处编辑或完善您的文案内容..."></textarea>
                </div>
            </div>

            <div class="px-8 py-6 border-t border-slate-50 flex items-center justify-end bg-white shrink-0">
                <div class="flex items-center gap-3">
                    <button
                        class="h-11 px-8 rounded-xl bg-primary text-white text-sm font-[1000] shadow-lg shadow-[#0065fb]/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2"
                        @click="copy(editContent)">
                        <Icon name="el-icon-DocumentCopy" />
                        复制全文内容
                    </button>
                </div>
            </div>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
const show = defineModel<boolean>("show");
const editContent = defineModel<string>("content");

const close = () => {
    show.value = false;
};

const { copy } = useCopy();
</script>

<style scoped lang="scss">
.draft-textarea {
    width: 100%;
    height: 100%;
    resize: none;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    padding: 24px;
    font-size: 15px;
    line-height: 1.8;
    color: #334155;
    background-color: #f8fafc;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;

    &::placeholder {
        color: #cbd5e1;
        font-weight: 500;
    }

    &:focus {
        outline: none;
        background-color: #fff;
        border-color: #0065fb40;
        box-shadow: 0 0 0 4px #0065fb08, 0 10px 30px rgba(0, 0, 0, 0.02);
    }
}
</style>
