<template>
    <el-card class="!border-none mt-4" shadow="never" v-if="data.length > 0">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div
                v-for="(item, index) in data"
                :key="item.name"
                class="rounded-lg shadow-md overflow-hidden p-5"
                style="background-color: #f8f8f8"
                :style="{ borderTop: `2px solid ${topBorderColors[index % topBorderColors.length]}` }">
                <div>
                    <div class="font-bold text-lg mb-1" style="color: #1f2937">
                        {{ item.name }}
                    </div>
                    <p class="text-xs min-h-[30px]" style="color: #4b5563">
                        {{ item.description }}
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t" style="border-color: #f3f4f6">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-xs uppercase font-semibold" style="color: #6b7280">成本价/用户</div>
                            <div class="flex items-baseline mt-1">
                                <span class="text-2xl font-bold" style="color: #f59e0b">{{
                                    parsePrice(item.cast_unit).value
                                }}</span>
                                <span class="mx-1" style="color: #6b7280">/</span>
                                <span class="text-2xl font-bold" style="color: #0ea5e9">{{
                                    parsePrice(item.score).value
                                }}</span>
                                <span class="text-sm ml-1" style="color: #6b7280">{{ item.unit }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs uppercase font-semibold" style="color: #6b7280">调整价格</div>
                            <p class="font-mono mt-1" style="color: #1f2937">
                                <el-input
                                    class="w-[100px]"
                                    type="number"
                                    v-model="item.score"
                                    size="small"
                                    :step="2"
                                    v-input-number="{ decimal: 2, min: 0, max: 99999 }"></el-input>
                                <div class="text-sm ml-1" style="color: #6b7280">{{ item.unit }}</div>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </el-card>
</template>

<script setup lang="ts">
import { defineProps } from "vue";

const props = defineProps<{
    data: any[];
}>();

const topBorderColors = ["#88d4c5", "#b0a7d9", "#e0b0a8", "#a7cfb6", "#f0d9a6"];

function parsePrice(priceString: string | undefined) {
    if (!priceString) return { value: "N/A", unit: "" };
    const match = priceString.match(/([\d,.-]+)(.*)/);
    if (match) {
        return {
            value: match[1],
            unit: match[2].trim(),
        };
    }
    return { value: priceString, unit: "" };
}
</script>

<style scoped></style>
