-- Client feedback: "when creating the tour operator profile, there is
-- no need to put the budget type, budget type is only used when
-- creating a tour" -- an operator can run tours across multiple budget
-- tiers, so a single company-wide budget field never made sense here.
-- It was already dropped from the operator's public "At a glance" card
-- in favour of a per-tour value; this removes the now-unused column and
-- admin form field entirely.

ALTER TABLE `tour_operators` DROP COLUMN `budget_type`;
