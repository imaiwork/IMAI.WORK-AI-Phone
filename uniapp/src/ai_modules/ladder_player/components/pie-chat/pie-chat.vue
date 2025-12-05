<template>
	<view>
		<lechart ref="chartRef" custom-style="height: 470rpx"></lechart>
	</view>
</template>

<script lang="ts" setup>
import lechart from "@/ai_modules/ladder_player/components/lime-echart/components/l-echart/l-echart.vue";
const echarts = require("../../static/lime-echart/static/echarts.min.js");
import StartIcon from "@/ai_modules/ladder_player/static/icons/score_star.svg";
import StartFillIcon from "@/ai_modules/ladder_player/static/icons/score_star_fill.svg";
echarts.install = null;
const props = defineProps({
	indicator: {
		type: Array,
		default: [],
	},
});

const chartRef = shallowRef(null);
const option: any = {
	radar: {
		center: ["50%", "50%"], // 调整雷达图的中心位置
		radius: "60%", // 调整雷达图的半径大小
		indicator: props.indicator,
		name: {
			textStyle: {
				color: "#000000",
				fontWeight: "bold",
				fontSize: 14,
			},
			formatter: function (value: any, params: any, index: any) {
				const currentScore = Math.floor((params.score / 20) * 5);
				const maxScore = 5;
				// 根据分数动态生成图标
				let stars = "";
				for (let i = 0; i < maxScore; i++) {
					if (i < currentScore) {
						// 选中的星星图片
						stars += "{selectedStar|}";
					} else {
						// 未选中的星星图片
						stars += "{unselectedStar|}";
					}
				}
				return `{text|${value}}\n${stars}`;
			},
			rich: {
				text: {
					fontSize: 14,
					align: "center", // 居中对齐
					fontWeight: "bold",
					padding: [0, 0, 4, 0],
				},
				selectedStar: {
					height: 12,
					width: 12,
					align: "center",
					backgroundColor: {
						image: StartFillIcon, // 替换为选中星星图片路径
					},
				},
				unselectedStar: {
					height: 12,
					width: 12,
					align: "center",
					backgroundColor: {
						image: StartIcon, // 替换为未选中星星图片路径
					},
				},
			},
		},
		axisLine: {
			lineStyle: {
				color: "#E7E7E9",
			},
		},
		splitArea: {
			show: false,
		},
		splitLine: {
			lineStyle: {
				color: "#E7E7E9",
			},
		},
	},
	series: [
		{
			type: "radar",
			tooltip: {
				trigger: "item",
			},
			symbolSize: 0,
			areaStyle: {
				color: "#A6BAFB",
			},
			data: [
				{
					value: [],
					lineStyle: {
						width: 0,
					},
				},
			],
		},
	],
};

onMounted(() => {
	setTimeout(async () => {
		if (!chartRef.value) return;
		const myChart = await chartRef.value?.init(echarts);
		option.series[0].data[0].value = props.indicator.map(
			(item: any) => item.score
		);
		myChart.setOption(option);
	}, 300);
});
</script>

<style></style>
