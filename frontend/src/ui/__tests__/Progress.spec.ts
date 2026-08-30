import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import Progress from '../Progress.vue'

function widthOf(html: string): string {
  return html.match(/width:\s*([\d.]+%)/)?.[1] ?? ''
}

describe('Progress', () => {
  it('считает процент от value/max', () => {
    const wrapper = mount(Progress, { props: { value: 25, max: 100 } })
    expect(widthOf(wrapper.html())).toBe('25%')
  })

  it('ограничивает сверху 100%', () => {
    const wrapper = mount(Progress, { props: { value: 500, max: 100 } })
    expect(widthOf(wrapper.html())).toBe('100%')
  })

  it('не уходит ниже 0% при отрицательном value', () => {
    const wrapper = mount(Progress, { props: { value: -10, max: 100 } })
    expect(widthOf(wrapper.html())).toBe('0%')
  })

  it('корректно работает с произвольным max', () => {
    const wrapper = mount(Progress, { props: { value: 3, max: 4 } })
    expect(widthOf(wrapper.html())).toBe('75%')
  })

  it('возвращает 0% при нулевом max', () => {
    const wrapper = mount(Progress, { props: { value: 3, max: 0 } })
    expect(widthOf(wrapper.html())).toBe('0%')
  })

  it('проставляет aria-атрибуты', () => {
    const wrapper = mount(Progress, { props: { value: 10, max: 20 } })
    const bar = wrapper.get('[role="progressbar"]')
    expect(bar.attributes('aria-valuenow')).toBe('10')
    expect(bar.attributes('aria-valuemax')).toBe('20')
  })
})
