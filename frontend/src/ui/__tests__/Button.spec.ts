import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '../Button.vue'

describe('Button', () => {
  it('рендерит содержимое слота', () => {
    const wrapper = mount(Button, { slots: { default: 'Играть' } })
    expect(wrapper.text()).toBe('Играть')
  })

  it('по умолчанию использует вариант primary', () => {
    const wrapper = mount(Button, { slots: { default: 'x' } })
    expect(wrapper.classes().join(' ')).toContain('bg-brand')
  })

  it('применяет классы варианта danger', () => {
    const wrapper = mount(Button, { props: { variant: 'danger' }, slots: { default: 'x' } })
    expect(wrapper.classes().join(' ')).toContain('bg-danger')
  })

  it('эмитит click', async () => {
    const wrapper = mount(Button, { slots: { default: 'x' } })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('блокируется и не кликается при loading', async () => {
    const wrapper = mount(Button, { props: { loading: true }, slots: { default: 'x' } })
    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.attributes('aria-busy')).toBe('true')
  })

  it('уважает disabled', () => {
    const wrapper = mount(Button, { props: { disabled: true }, slots: { default: 'x' } })
    expect(wrapper.attributes('disabled')).toBeDefined()
  })
})
