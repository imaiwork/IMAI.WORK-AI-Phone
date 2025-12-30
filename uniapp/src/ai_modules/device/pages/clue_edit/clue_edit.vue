<template>
    <view class="h-screen flex flex-col">
        <view class="flex items-center gap-x-2 mx-4 mt-4">
            <view
                class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                @click="handleEditClue(-1)">
                <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                <text class="font-bold text-[32rpx]">手动输入</text>
            </view>
            <view
                class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]"
                @click="showClueGenPopup = true">
                <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                <text class="text-white font-bold text-[32rpx]">AI生成</text>
            </view>
        </view>
        <view class="font-bold text-[30rpx] px-4 mt-[60rpx]">线索词（{{ clueList.length }}）</view>
        <view class="grow min-h-0 mt-[32rpx]">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 flex flex-wrap gap-4 pb-[100rpx]">
                    <view
                        v-for="(item, index) in clueList"
                        :key="index"
                        class="clue-item flex gap-x-2"
                        @click="handleEditClue(index)">
                        <view>{{ item }}</view>
                        <view
                            class="w-4 h-4 flex items-center justify-center bg-[#0000004d] rounded-full"
                            @click.stop="handleDeleteClue(index)">
                            <u-icon name="close" size="16" color="#ffffff"></u-icon>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white flex-shrink-0 pb-5 pt-4 px-6">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center"
                @click="handleSave">
                确定保存
            </view>
        </view>
    </view>
    <clue-gen-pop v-model="showClueGenPopup" @confirm="handleClueGenConfirm" />
    <clue-edit ref="clueEditRef" v-model="showClueEdit" title="线索词" @confirm="handleClueConfirm" />
</template>

<script setup lang="ts">
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import useMaterialStore from "@/ai_modules/device/stores/material";
import ClueEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import ClueGenPop from "@/ai_modules/device/components/clue-gen-pop/clue-gen-pop.vue";

const materialStore = useMaterialStore();

const { emit } = useEventBusManager();

const clueList = ref<any[]>([]);
const clueIndex = ref<number>(-1);
const editClueIndex = ref<number>(-1);
const showClueGenPopup = ref(false);

const showClueEdit = ref(false);
const clueEditRef = ref<InstanceType<typeof ClueEdit>>();

const handleEditClue = (index: number) => {
    showClueEdit.value = true;

    if (index >= 0) {
        editClueIndex.value = index;
        clueEditRef.value?.setFormData(clueList.value[index]);
    }
};

const handleDeleteClue = (index: number) => {
    clueList.value.splice(index, 1);
};

const handleClueConfirm = (data: string) => {
    if (editClueIndex.value >= 0) {
        clueList.value[editClueIndex.value] = data;
    } else {
        clueList.value.push(data);
    }
    showClueEdit.value = false;
    editClueIndex.value = -1;
};

const handleClueGenConfirm = (ref: any[]) => {
    clueList.value.push(...ref);
    showClueGenPopup.value = false;
};

const handleSave = async () => {
    if (!clueList.value.length) {
        uni.$u.toast("请至少输入一个线索词");
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.CLUE_LIST,
        data: clueList.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.index >= 0) {
        clueIndex.value = options.index;
        clueList.value = materialStore.clueList;
    }
});
</script>

<style scoped lang="scss">
.clue-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] px-4 py-2 font-bold;
}
</style>
