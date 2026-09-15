/* eslint-disable indent, max-len, object-curly-spacing, prettier/prettier */

/**
 * Import function triggers from their respective submodules:
 *
 * const {onCall} = require("firebase-functions/v2/https");
 * const {onDocumentWritten} = require("firebase-functions/v2/firestore");
 *
 * See a full list of supported triggers at https://firebase.google.com/docs/functions
 */

const {setGlobalOptions} = require("firebase-functions");
const {onSchedule} = require("firebase-functions/v2/scheduler");
const {defineString} = require("firebase-functions/params");

const {MongoClient} = require("mongodb");
const {getDatabase} = require("firebase-admin/database");
const {initializeApp} = require("firebase-admin/app");

initializeApp();

const mongodbUri = defineString("MONGODB_URI");

let mongoClientPromise;

exports.syncLiveEnergy = onSchedule({
    schedule: "every 1 minutes",
    timeZone: "Asia/Manila",
    region: "asia-southeast1",
}, async () => {
    const liveSnapshot = await getDatabase().ref("Live").once("value");
    const liveData = liveSnapshot.val() || {};
    const recordedAt = new Date();
    const minuteKey = recordedAt.toISOString().slice(0, 16);

    if (!mongoClientPromise) {
        const client = new MongoClient(mongodbUri.value());
        mongoClientPromise = client.connect();
    }

    const client = await mongoClientPromise;
    const collection = client.db("wattwise").collection("energy_readings");

    const writes = Object.entries(liveData)
        .filter(([, reading]) => reading && typeof reading === "object")
        .map(([deviceId, reading]) => collection.updateOne(
            {_id: `${deviceId}:${minuteKey}`},
            {
                $set: {
                    voltage: Number(reading.voltage || 0),
                    current: Number(reading.current || 0),
                    power: Number(reading.power || 0),
                    energy: Number(reading.energy || 0),
                    frequency: reading.frequency == null ? null :
                        Number(reading.frequency),
                    power_factor: reading.pf == null ? null :
                        Number(reading.pf),
                    device_id: deviceId,
                    recorded_at: recordedAt,
                    firebase_path: `Live/${deviceId}`,
                    synced_at: new Date(),
                },
            },
            {upsert: true},
        ));

    await Promise.all(writes);
});

// For cost control, you can set the maximum number of containers that can be
// running at the same time. This helps mitigate the impact of unexpected
// traffic spikes by instead downgrading performance. This limit is a
// per-function limit. You can override the limit for each function using the
// `maxInstances` option in the function's options, e.g.
// `onRequest({ maxInstances: 5 }, (req, res) => { ... })`.
// NOTE: setGlobalOptions does not apply to functions using the v1 API. V1
// functions should each use functions.runWith({ maxInstances: 10 }) instead.
// In the v1 API, each function can only serve one request per container, so
// this will be the maximum concurrent request count.
setGlobalOptions({maxInstances: 10});

// Create and deploy your first functions
// https://firebase.google.com/docs/functions/get-started

// exports.helloWorld = onRequest((request, response) => {
//   logger.info("Hello logs!", {structuredData: true});
//   response.send("Hello from Firebase!");
// });
