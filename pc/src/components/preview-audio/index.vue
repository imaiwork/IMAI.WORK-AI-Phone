<template>
    <popup
        ref="audioPlayerRef"
        width="800px"
        confirm-button-text=""
        cancel-button-text=""
        style="background-color: var(--app-bg-color-2)"
        :show-close="false"
        @close="close">
        <div class="-my-4">
            <div
                class="absolute right-4 top-4 w-6 h-6 rounded-full bg-[rgba(255,255,255,0.05)] flex items-center justify-center cursor-pointer hover:bg-[rgba(255,255,255,0.1)]"
                @click="close">
                <Icon name="el-icon-Close" color="#8B9199"></Icon>
            </div>
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path
                        opacity="0.1"
                        d="M6 0.5H18C21.0376 0.5 23.5 2.96243 23.5 6V18C23.5 21.0376 21.0376 23.5 18 23.5H6C2.96243 23.5 0.5 21.0376 0.5 18V6C0.5 2.96243 2.96243 0.5 6 0.5Z"
                        stroke="white" />
                    <path d="M8 10V14" stroke="white" stroke-width="1.2" />
                    <path d="M16 10V14" stroke="white" stroke-width="1.2" />
                    <path d="M12 8V16" stroke="white" stroke-width="1.2" />
                </svg>
                <div class="text-white text-[20px] font-bold">预览</div>
            </div>
            <div class="w-full h-[200px] flex items-center justify-center">
                <audio class="w-full" ref="audioRef" :src="audioUrl" controls autoplay></audio>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const emit = defineEmits(["close"]);

const audioUrl = ref("");
const audioRef = ref();

const audioPlayerRef = shallowRef();
const open = () => {
    audioPlayerRef.value.open();
};

const setUrl = (url: string) => {
    audioUrl.value = url;
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    setUrl,
});
</script>

<style scoped></style>
