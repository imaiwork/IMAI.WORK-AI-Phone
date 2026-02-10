<template>
    <div class="grid grid-cols-5 gap-3 p-1">
        <div class="flex flex-col items-center w-full group" v-for="(item, index) in dateList" :key="index">
            <div
                class="w-full text-center py-1.5 rounded-lg bg-slate-50 text-[11px] font-medium text-slate-500 group-hover:bg-[#0065fb]/10 group-hover:text-primary transition-colors">
                Day {{ item.order_day }}
            </div>

            <div class="flex flex-col gap-1.5 mt-2 w-full px-1">
                <template v-if="item.timeList.length < 3">
                    <div
                        v-for="(value, vIndex) in item.timeList"
                        :key="vIndex"
                        class="push-time-tag"
                        @click="emit('edit', value.id)">
                        {{ value.push_time }}
                    </div>
                </template>

                <template v-else>
                    <ElPopover placement="bottom" :width="240" popper-class="!rounded-2xl !p-3 !border-slate-100 ">
                        <template #reference>
                            <div class="more-count-tag">
                                <Icon name="el-icon-CirclePlus" :size="10"></Icon>
                                <span class="ml-1">{{ item.timeList.length }} 条推送</span>
                            </div>
                        </template>

                        <div class="flex flex-col gap-3 max-h-[400px] overflow-y-auto pr-1">
                            <div class="text-[13px] font-black text-slate-800 mb-1 flex items-center justify-between">
                                <span>第 {{ item.order_day }} 天详情</span>
                                <span class="text-primary text-[11px]">共{{ item.timeList.length }}条</span>
                            </div>
                            <div
                                v-for="(value, pIndex) in item.timeList"
                                :key="pIndex"
                                class="p-2 rounded-xl bg-slate-50 border border-slate-100 hover:border-[#0065fb]/30 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="text-[11px] font-black text-primary bg-[#0065fb]/10 px-2 py-0.5 rounded-md">
                                        {{ value.push_time }}
                                    </span>
                                    <div
                                        class="text-[10px] text-slate-400 hover:text-primary cursor-pointer font-medium"
                                        @click="emit('edit', value.id)">
                                        编辑
                                    </div>
                                </div>
                                <FileItem :file="value" class="!bg-transparent !p-0" />
                            </div>
                        </div>
                    </ElPopover>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import FileItem from "./file-item.vue";

const props = defineProps<{
    dateList: Record<string, any>[];
}>();

const emit = defineEmits<{
    (e: "edit", data: any): void;
}>();
</script>

<style scoped lang="scss">
.push-time-tag {
    @apply text-[10px] text-primary bg-[#0065fb]/5 border border-[#0065fb]/10 text-center py-1 rounded-md cursor-pointer font-medium transition-all hover:bg-primary hover:text-white hover:shadow-light hover:shadow-[#0065fb]/20;
}

.more-count-tag {
    @apply text-[10px] text-white bg-primary text-center py-1 rounded-md cursor-pointer font-black shadow-light shadow-[#0065fb]/30 hover:scale-105 transition-all flex items-center justify-center;
}
</style>
