import Sortable from "sortablejs";
import type { Directive, DirectiveBinding } from "vue";

export default {
	mounted(el: HTMLElement, binding: DirectiveBinding) {
		const options = binding.value;
		options.forEach((item: any) => {
			new Sortable(
				el.querySelector(item.selector) as HTMLElement,
				item.options
			);
		});
	},
};
