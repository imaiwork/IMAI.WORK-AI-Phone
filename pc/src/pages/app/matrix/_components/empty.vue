<template>
    <div class="flex flex-col items-center justify-center">
        <div
            class="w-12 h-12 rounded-xl flex items-center justify-center border border-dashed border-[#E2E8F0 ] hover:border-[#0065fb] cursor-pointer"
            @click="handleClick">
            <Icon name="el-icon-Plus"></Icon>
        </div>
        <div class="text-lg mt-4">{{ title }}</div>
        <div class="text-[#00000080]">{{ msg }}</div>
        <ElButton type="primary" class="!px-4 !h-10 !rounded-full mt-5" @click="handleClick">{{ btnText }}</ElButton>
    </div>
</template>

<script setup lang="ts">
const props = defineProps({
    btnText: {
        type: String,
        default: "发布视频",
    },
    msg: {
        type: String,
        default: "返回首页快速发布你的专属视频",
    },
    title: {
        type: String,
        default: "暂无内容",
    },
    customClick: {
        type: Function,
        default: () => {},
    },
});

const handleClick = () => {
    if (props.customClick) {
        props.customClick();
    } else {
        navigateTo("/app/matrix?type=1");
        setTimeout(() => {
            window.location.reload();
        }, 100);
    }
};
</script>

<style scoped></style>
