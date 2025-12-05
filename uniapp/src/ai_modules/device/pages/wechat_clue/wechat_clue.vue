<template>
    <view class="h-screen flex flex-col pt-4">
        <view class="text-[30rpx] font-bold px-4">线索列表（已选：{{ selectedList.length }}）</view>
        <view class="grow min-h-0 mt-4">
            <z-paging
                ref="pagingRef"
                v-model="taskList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-4 px-4">
                    <view
                        v-for="(item, index) in taskList"
                        :key="index"
                        class="flex gap-x-4 bg-white rounded-[20rpx]"
                        @click="handleSelect(index, item)">
                        <view class="flex-1">
                            <clue-card
                                :data="{
                                    name: item.name,
                                    time: item.create_time,
                                    total: item.crawl_number,
                                    added: item.completed_add_count,
                                }"
                                :type="2" />
                        </view>
                        <view class="flex items-center justify-center mr-4 flex-shrink-0">
                            <view
                                class="w-5 h-5 rounded-full"
                                :class="[
                                    selectedList.includes(index)
                                        ? 'bg-primary flex items-center justify-center'
                                        : 'border border-solid border-[#00000033]',
                                ]">
                                <u-icon
                                    name="checkmark"
                                    color="#ffffff"
                                    size="20"
                                    v-if="selectedList.includes(index)"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="bg-white pt-2 px-4 pb-5">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '20rpx', fontWeight: 'bold' }"
                :disabled="!selectedList.length"
                @click="handleConfirm"
                >确认</u-button
            >
        </view>
    </view>
</template>

<script setup lang="ts">
import { getTaskList } from "@/api/sph";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import ClueCard from "@/ai_modules/device/components/clue-card/clue-card.vue";

const pagingRef = shallowRef();
const taskList = ref<any[]>([]);

const selectedList = ref<number[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getTaskList({ page_no, page_size });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const handleSelect = (index: number, item: any) => {
    const { crawl_number } = item;
    if (crawl_number == 0) {
        uni.$u.toast("该线索没有可采集数量");
        return;
    }
    selectedList.value = selectedList.value.includes(index)
        ? selectedList.value.filter((item) => item !== index)
        : [...selectedList.value, index];
};

const handleConfirm = () => {
    if (!selectedList.value.length) {
        uni.$u.toast("请选择线索");
        return;
    }
    uni.$emit("confirm", {
        type: ListenerTypeEnum.WECHAT_CLUE,
        data: selectedList.value.map((item) => {
            const task = taskList.value[item];
            return {
                id: task.id,
                name: task.name,
                time: task.create_time,
                total: task.crawl_number,
                added: task.completed_add_count,
                file_type: 2,
            };
        }),
    });
    uni.navigateBack();
};
</script>

<style scoped lang="scss"></style>
