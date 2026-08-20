<template>
    <div class="map-cats-wrap w-full mt-4 mb-2">
        <div class="header flex items-center justify-between mb-2">
            <span class="mc-title text-[13px] text-[#6b7280]">选一个类目，AI 自动抓取本地商家</span>
            <div v-if="totalPages > 1" class="pager flex items-center gap-1.5">
                <button class="page-arrow" :disabled="page === 0" @click="prev">‹</button>
                <span class="page-dots flex items-center gap-1">
                    <span
                        v-for="(_, idx) in totalPages"
                        :key="idx"
                        class="dot"
                        :class="{ active: idx === page }"
                        @click="page = idx" />
                </span>
                <button class="page-arrow" :disabled="page === totalPages - 1" @click="next">›</button>
            </div>
        </div>

        <div class="viewport overflow-hidden" @wheel.stop>
            <div
                class="track flex"
                :style="{ width: `${totalPages * 100}%`, transform: `translateX(-${page * (100 / totalPages)}%)` }">
                <div
                    v-for="(group, gi) in pagedCats"
                    :key="gi"
                    class="page-grid"
                    :style="{ width: `${100 / totalPages}%` }">
                    <div
                        v-for="c in group"
                        :key="c.name"
                        class="mc-card"
                        @click="$emit('pick', c.name)">
                        <div class="mc-emoji">{{ c.emoji }}</div>
                        <div class="mc-info">
                            <div class="mc-name">{{ c.name }}</div>
                            <div class="mc-desc">{{ c.desc }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Cat {
    emoji: string;
    name: string;
    desc: string;
}

defineEmits<{ (e: "pick", name: string): void }>();

const MAP_CATS: Cat[] = [
    { emoji: "🍜", name: "餐饮美食", desc: "餐厅、小吃、火锅" },
    { emoji: "☕", name: "咖啡奶茶", desc: "咖啡厅、茶饮" },
    { emoji: "🏦", name: "银行机构", desc: "银行、信用社" },
    { emoji: "🏥", name: "医疗健康", desc: "医院、诊所、药店" },
    { emoji: "🏨", name: "酒店住宿", desc: "酒店、民宿、公寓" },
    { emoji: "🛒", name: "购物商场", desc: "超市、购物中心" },
    { emoji: "💄", name: "美容美发", desc: "美容院、理发店" },
    { emoji: "🏋️", name: "健身运动", desc: "健身房、瑜伽馆" },
    { emoji: "🚗", name: "汽车服务", desc: "4S 店、维修、洗车" },
    { emoji: "🏫", name: "教育培训", desc: "学校、培训机构" },
    { emoji: "🌿", name: "公园景区", desc: "公园、景点、广场" },
    { emoji: "🏛️", name: "政务机关", desc: "政府、派出所、街道" },
];

const PAGE_SIZE = 6; // 3 列 × 2 行

const page = ref(0);
const totalPages = computed(() => Math.max(1, Math.ceil(MAP_CATS.length / PAGE_SIZE)));
const pagedCats = computed(() => {
    const out: Cat[][] = [];
    for (let i = 0; i < MAP_CATS.length; i += PAGE_SIZE) {
        out.push(MAP_CATS.slice(i, i + PAGE_SIZE));
    }
    return out;
});

function prev() {
    if (page.value > 0) page.value--;
}
function next() {
    if (page.value < totalPages.value - 1) page.value++;
}
</script>

<style lang="scss" scoped>
.header {
    .pager {
        .page-arrow {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid #ebedf0;
            background: #fff;
            color: #6b7280;
            font-size: 14px;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            &:hover:not(:disabled) {
                border-color: #93c5fd;
                color: #2563eb;
            }
            &:disabled {
                opacity: 0.35;
                cursor: not-allowed;
            }
        }
        .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #d1d5db;
            cursor: pointer;
            transition: all 0.15s;
            &.active {
                background: #2563eb;
                width: 14px;
                border-radius: 3px;
            }
        }
    }
}

.viewport {
    width: 100%;
}
.track {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.page-grid {
    flex-shrink: 0;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(2, auto);
    gap: 8px;
    padding-right: 0;
}

.mc-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #fff;
    border: 1px solid #f0f1f4;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s;
    min-width: 0;

    &:hover {
        border-color: #93c5fd;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
        transform: translateY(-1px);
    }
}
.mc-emoji {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.mc-info {
    flex: 1;
    min-width: 0;
}
.mc-name {
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mc-desc {
    font-size: 11px;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
