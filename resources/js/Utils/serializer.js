/**
 * Serializes an entity object to a JSON string.
 *
 * @param {object} entity - The entity to serialize
 * @returns {string} JSON string representation
 */
export function serialize(entity) {
  return JSON.stringify(entity)
}

/**
 * Deserializes a JSON string back to an object.
 *
 * @param {string} json - The JSON string to parse
 * @returns {object} The parsed object
 */
export function deserialize(json) {
  return JSON.parse(json)
}

/**
 * Performs a serialization round-trip: serialize then deserialize.
 * Useful for ensuring data consistency through JSON storage.
 *
 * @param {object} entity - The entity to round-trip
 * @returns {object} A deep copy of the entity via JSON round-trip
 */
export function roundTrip(entity) {
  return deserialize(serialize(entity))
}
