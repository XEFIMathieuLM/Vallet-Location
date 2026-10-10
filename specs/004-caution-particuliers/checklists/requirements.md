# Specification Quality Checklist: Caution des particuliers

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-10-10
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

- Markers resolved 2026-10-10: amount per machine category with a default (US6, FR-017); the tool records a deposit collected outside it, bank pre-authorisation deferred to a later spec (FR-006).
- Clarify 2026-10-10 confirmed: FR-013 retention (HT, capped, not editable), restitution needs an explicit "no damage" confirmation (FR-012), departure blocked while customer type is unset (FR-005), deposit not sent to billing, payment correction allowed until settlement with a reason (FR-010).
