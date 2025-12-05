<template>
    <div class="w-full h-full py-6">
        <ElScrollbar>
            <div class="flex flex-col gap-y-4 px-6">
                <div class="">
                    <div class="flex items-center gap-x-4">
                        <img src="@/assets/images/7_day.png" class="w-6 h-6" />
                        <div>
                            已在当前流程停留<span class="text-primary font-bold mx-1">{{ flowData.stay_flow_day }}</span
                            >天；已在当前阶段停留
                            <span class="text-primary font-bold mx-1">{{ flowData.stay_stage_day }}</span
                            >天
                        </div>
                    </div>
                    <div class="shadow-lighter rounded-lg bg-white mt-3 px-4 h-[148px] flex flex-col">
                        <div class="h-[52px] flex items-center justify-between border-b border-[#E7E7E7]">
                            <div class="flex items-center gap-x-2">
                                <img src="../_assets/images/flow_title_img.png" class="w-4 h-4" />
                                <span>{{ flowData.flow_name }}</span>
                            </div>
                            <div class="text-[#9E9E9E] text-xs">进入流程日期：{{ flowData.join_flow_time || "-" }}</div>
                        </div>
                        <div class="grow min-h-0 w-full">
                            <div class="w-full h-full flex items-center whitespace-nowrap overflow-x-auto px-6">
                                <div
                                    v-for="(item, index) in flowData.stage"
                                    :key="index"
                                    class="flex items-center gap-2 w-[200px] flex-shrink-0">
                                    <div class="flex flex-col items-center gap-2">
                                        <Icon
                                            v-if="flowData.stage_id >= item.stage_id"
                                            name="el-icon-SuccessFilled"
                                            color="var(--el-color-success)"
                                            :size="20"></Icon>
                                        <Icon
                                            v-else
                                            name="el-icon-LocationFilled"
                                            color="var(--color-primary)"
                                            :size="20"></Icon>
                                        <span class="text-[#5E656E]">{{ item.sub_stage_name }}</span>
                                    </div>
                                    <div class="flex-1 flex justify-center">
                                        <Icon
                                            name="local-icon-flow_arrow_right"
                                            :color="
                                                flowData.stage_id >= item.stage_id
                                                    ? 'var(--el-color-success)'
                                                    : 'var(--color-primary)'
                                            "
                                            :size="20"></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    flowData: Record<string, any>;
}>();
</script>

<style scoped></style>
