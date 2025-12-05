import Sortable from "sortablejs";
import type { Directive, DirectiveBinding } from "vue";

export default defineNuxtPlugin((nuxtApp) => {
	nuxtApp.vueApp.directive("draggable", {
		mounted(el: HTMLElement, binding: DirectiveBinding) {
			const options = binding.value;
			options.forEach((item: any) => {
				new Sortable(
					el.querySelector(item.selector) as HTMLElement,
					item.options
				);
			});
		},
	});
});
// 移除元素
const removeEl = (el) => el.parentNode && el.parentNode.removeChild(el);
