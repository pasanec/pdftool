// src/shims-vue.d.ts

declare module '*.vue' {
	import Vue from 'vue'
	export default Vue
}
declare module '*.svg' {
	const content: string
	export default content
}
declare module '*.svg?raw' {
	const content: string
	export default content
}
