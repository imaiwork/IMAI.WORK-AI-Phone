<template>
    <view class="h-screen flex flex-col">
        <view class="font-bold text-[30rpx] mx-4 mt-4">营销主题({{ dataList.length }})</view>
        <view class="grow min-h-0 mt-4">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 flex flex-wrap gap-2">
                    <view
                        class="rounded-[24rpx] bg-black px-[42rpx] h-[80rpx] flex items-center gap-x-1"
                        @click="handleEditKeywords(-1)">
                        <u-icon name="plus" size="20" color="#ffffff"></u-icon>
                        <text class="text-xs text-white font-bold">添加</text>
                    </view>
                    <view
                        v-for="(item, index) in dataList"
                        :key="index"
                        class="rounded-[24rpx] bg-white px-[26rpx] h-[80rpx] flex items-center gap-x-1 font-bold"
                        @click="handleEditKeywords(index)">
                        {{ item }}
                        <view
                            class="w-4 h-4 flex items-center justify-center bg-[#0000004d] rounded-full"
                            @click.stop="handleDeleteKeywords(index)">
                            <u-icon name="close" size="16" color="#ffffff"></u-icon>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white px-6 pt-4 pb-5">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '16rpx', fontWeight: 'bold' }"
                @click="handleConfirm"
                >确定保存</u-button
            >
        </view>
    </view>
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        title="营销主题"
        @confirm="handleKeywordsConfirm"
        @close="
            showKeywordsEdit = false;
            editIndex = -1;
        " />
</template>

<script setup lang="ts">
import useMaterialStore, { ListName } from "@/ai_modules/device/stores/material";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";

const materialStore = useMaterialStore();

const dataList = ref<any[]>(JSON.parse(JSON.stringify(materialStore.marketingList)));
const editIndex = ref<number>(-1);
const showKeywordsEdit = ref(false);
const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>();

const handleEditKeywords = (index: number) => {
    editIndex.value = index;
    showKeywordsEdit.value = true;
    if (index > -1) {
        keywordsEditRef.value?.setFormData(dataList.value[index]);
    }
};

const handleKeywordsConfirm = (value: string) => {
    if (editIndex.value === -1) {
        dataList.value.push(value);
    } else {
        dataList.value[editIndex.value] = value;
    }
    showKeywordsEdit.value = false;
};

const handleDeleteKeywords = (index: number) => {
    dataList.value.splice(index, 1);
};
const handleConfirm = () => {
    if (dataList.value.length === 0) {
        uni.$u.toast("至少添加一个营销主题");
        return;
    }
    materialStore.setList("marketingList", dataList.value);
    uni.navigateBack();
};
</script>

<style scoped></style>
