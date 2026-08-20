<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <view class="flex-shrink-0 px-[28rpx] h-[100rpx] flex items-center justify-between">
            <view class="flex items-center gap-[10rpx]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[32rpx] font-extrabold text-[#0D1117]">线索列表</text>
            </view>
            <view
                class="flex items-center gap-[6rpx] h-[52rpx] px-[20rpx] rounded-full transition-all"
                :class="selectedList.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                <text class="text-xs font-bold" :class="selectedList.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                    {{ selectedList.length }}
                </text>
                <text class="text-[22rpx]" :class="selectedList.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'">
                    已选
                </text>
            </view>
        </view>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="taskList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-[16rpx] px-[24rpx] pt-[16rpx]">
                    <view
                        v-for="(item, index) in taskList"
                        :key="index"
                        class="flex items-center gap-[16rpx] bg-white rounded-[24rpx] overflow-hidden transition-all"
                        :class="
                            selectedList.includes(index)
                                ? 'shadow-[0_4rpx_16rpx_rgba(0,101,251,0.12),0_0_0_2rpx_rgba(0,101,251,0.25)]'
                                : 'shadow-[0_2rpx_8rpx_rgba(0,0,0,0.06)]'
                        "
                        @click="handleSelect(index, item)">
                        <view
                            class="flex-shrink-0 w-[6rpx] self-stretch transition-all"
                            :class="selectedList.includes(index) ? 'bg-primary' : 'bg-transparent'" />

                        <view class="flex-1 min-w-0 py-[4rpx]">
                            <clue-card
                                :data="{
                                    name: item.name,
                                    time: item.create_time,
                                    total: item.crawl_number,
                                    added: item.completed_add_count,
                                }"
                                :type="2" />
                        </view>

                        <view class="flex-shrink-0 mr-[28rpx]">
                            <view
                                class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center transition-all"
                                :class="
                                    selectedList.includes(index)
                                        ? 'bg-primary shadow-[0_4rpx_10rpx_rgba(0,101,251,0.35)]'
                                        : 'border-2 border-solid border-[#D1D5DB] bg-white'
                                ">
                                <u-icon
                                    v-if="selectedList.includes(index)"
                                    name="checkmark"
                                    color="#ffffff"
                                    size="22" />
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-[24rpx] pt-[20rpx] pb-[40rpx] shadow-[0_-2rpx_12rpx_rgba(0,0,0,0.06)]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] transition-all"
                :class="selectedList.length > 0 ? 'shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]' : 'bg-[#E5E7EB]'"
                :style="selectedList.length > 0 ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)' : ''"
                @click="handleConfirm">
                <text
                    class="text-[30rpx] font-extrabold"
                    :class="selectedList.length > 0 ? 'text-white' : 'text-[#9CA3AF]'">
                    确认选择
                </text>
                <text v-if="selectedList.length > 0" class="text-xs text-[#ffffff]/70 font-medium">
                    ({{ selectedList.length }})
                </text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getTaskList } from "@/api/sph";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import ClueCard from "@/ai_modules/device/components/clue-card/clue-card.vue";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

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
    emit("confirm", {
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

<style scoped></style>
