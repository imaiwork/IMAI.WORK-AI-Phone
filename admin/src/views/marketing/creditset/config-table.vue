<template>
    <el-card class="!border-none mt-4" shadow="never" v-if="data.length > 0">
        <template #header>
            <div class="font-bold">{{ title }}</div>
        </template>
        <el-table :data="data">
            <el-table-column prop="name" label="名称" min-width="120">
                <template #default="{ row }">
                    <el-tag>{{ row.name }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="成本" min-width="300">
                <template #default="{ row }">
                    <el-tag type="warning">{{ row.cast_unit }}</el-tag>
                    <div class="text-xs">
                        {{ row.description }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column prop="score" label="用户价格" width="300">
                <template #default="{ row }">
                    <el-input v-model="row.score" type="number" v-number-input="{ decimal: 2 }" :min="0" :step="2">
                        <template #append>
                            {{ row.unit }}
                        </template>
                    </el-input>
                </template>
            </el-table-column>
        </el-table>
    </el-card>
</template>

<script setup lang="ts">
import { defineProps } from "vue";

const props = defineProps<{
    title: string;
    data: any[];
}>();
</script>

<style scoped></style>
