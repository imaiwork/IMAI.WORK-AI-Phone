export function useCopy() {
	const copy = (text: string) => {
		try {
			uni.setClipboardData({
				data: String(text),
				success() {
					uni.showToast({
						title: "复制成功",
						icon: "success",
						duration: 2000,
					});
				},
			});
		} catch (error) {
			uni.showToast({
				title: "复制失败",
				icon: "none",
				duration: 2000,
			});
		}
	};
	return {
		copy,
	};
}
