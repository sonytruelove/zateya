import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import Badge from '../Badge.vue'

describe('Badge', () => {
  it('рендерит текст слота', () => {
    const wrapper = mount(Badge, { slots: { default: 'Активна' } })
    expect(wrapper.text()).toBe('Активна')
  })

  it('по умолчанию нейтральный тон', () => {
    const wrapper = mount(Badge, { slots: { default: 'x' } })
    expect(wrapper.classes().join(' ')).toContain('text-ink-soft')
  })

  it('применяет тон success', () => {
    const wrapper = mount(Badge, { props: { tone: 'success' }, slots: { default: 'x' } })
    expect(wrapper.classes().join(' ')).toContain('text-success')
  })

  it('показывает точку при dot=true', () => {
    const wrapper = mount(Badge, { props: { tone: 'brand', dot: true }, slots: { default: 'x' } })
    expect(wrapper.find('.rounded-full').exists()).toBe(true)
  })

  it('не показывает точку по умолчанию', () => {
    const wrapper = mount(Badge, { slots: { default: 'x' } })
    expect(wrapper.find('.rounded-full').exists()).toBe(false)
  })
})
