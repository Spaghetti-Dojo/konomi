import { describe, it, expect, beforeEach } from '@jest/globals';
import { assertAnchorElement, assertIsPositiveInt } from '../../../../../../sources/Blocks/UserProfile/view/asserts';

describe('assertAnchorElement', () => {
  let anchor: HTMLAnchorElement;
  let div: HTMLDivElement;

  beforeEach(() => {
    anchor = document.createElement('a');
    div = document.createElement('div');
  });

  it('should not throw if element is an anchor', () => {
    expect(() => assertAnchorElement(anchor)).not.toThrow();
  });

  it('should throw if element is not an anchor', () => {
    expect(() => assertAnchorElement(div)).toThrow('Expected an anchor element');
    expect(() => assertAnchorElement(null)).toThrow('Expected an anchor element');
    expect(() => assertAnchorElement(undefined)).toThrow('Expected an anchor element');
    expect(() => assertAnchorElement({})).toThrow('Expected an anchor element');
  });
});

describe('assertIsPositiveInt', () => {
  it('should not throw for positive integers', () => {
    expect(() => assertIsPositiveInt(1)).not.toThrow();
    expect(() => assertIsPositiveInt(42)).not.toThrow();
    expect(() => assertIsPositiveInt(Number.MAX_SAFE_INTEGER)).not.toThrow();
  });

  it('should throw for non-numbers', () => {
    expect(() => assertIsPositiveInt('1')).toThrow('Expected a number');
    expect(() => assertIsPositiveInt(null)).toThrow('Expected a number');
    expect(() => assertIsPositiveInt(undefined)).toThrow('Expected a number');
    expect(() => assertIsPositiveInt({})).toThrow('Expected a number');
  });

  it('should throw for non-integer numbers', () => {
    expect(() => assertIsPositiveInt(1.5)).toThrow('Expected an integer');
    expect(() => assertIsPositiveInt(-1.5)).toThrow('Expected an integer');
  });

  it('should throw for zero or negative integers', () => {
    expect(() => assertIsPositiveInt(0)).toThrow('Expected a positive integer');
    expect(() => assertIsPositiveInt(-1)).toThrow('Expected a positive integer');
    expect(() => assertIsPositiveInt(-100)).toThrow('Expected a positive integer');
  });
});
